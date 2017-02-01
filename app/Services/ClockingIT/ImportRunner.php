<?php

namespace NeoClocking\Services\ClockingIT;

use Carbon\Carbon;
use Closure;
use DB;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use NeoClocking\Exceptions\ImportFailedException;
use NeoClocking\Exceptions\SkipRowImportException;
use stdClass;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class ImportRunner
{
    /**
     * How many row we fetch from source Database at once
     * @const int
     */
    const DATABASE_FETCH_BLOCK_SIZE = 500;

    /**
     * @const int
     */
    const PROGRESS_BAR_INCREMENT = 100;

    /**
     * @const int
     */
    const UPDATE_PROGRESS_RATE = 250;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var ProgressBar
     */
    protected $progressBar;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var Closure
     */
    protected $modelFactory;

    /**
     * @var array
     */
    protected $keys;

    /**
     * @var array
     */
    protected $columns;

    /**
     * @var array ['columnName' => ['operator', 'value']]
     */
    protected $selectConditions;

    /**
     * @var string
     */
    protected $sourceTable;

    /**
     * @var Closure
     */
    protected $endMerge;

    /**
     * @var Closure
     */
    protected $afterRowHook;

    /**
     * @var Closure
     */
    protected $beforeRowHook;

    /**
     * @var Closure
     */
    protected $afterImportCompleted;

    /**
     * @var Builder
     */
    protected $query;

    /**
     * @param OutputInterface $output
     * @param Connection $db
     */
    public function __construct(OutputInterface $output, Connection $db)
    {
        $this->output = $output;
        $this->db = $db;
    }

    /**
     * Set the table on the target database.
     *
     * @param string $table
     * @param string $sourceTable
     */
    public function setTable($table, $sourceTable)
    {
        $this->table = $table;
        $this->sourceTable = $sourceTable;
    }

    /**
     * Set the primary keys that will be used to update the target table.
     *
     * @param $keys
     */
    public function setKeys($keys)
    {
        $this->keys = $keys;
    }

    /**
     * Set the conditions for the source selection
     *
     * @param array $selectConditions ['columnName' => ['operator', 'value']]
     */
    public function setConditions(array $selectConditions)
    {
        $this->selectConditions = $selectConditions;
    }

    /**
     * Set the columns relations between target and source.
     * Target column => Source column
     *
     * @param array $columns
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;
    }

    /**
     * Set the model to use.
     *
     * @param Closure $modelFactory
     */
    public function setModelFactory($modelFactory)
    {
        $this->modelFactory = $modelFactory;
    }

    /**
     * Set closure to call to get additional values
     *
     * @param Closure|null $endMerge Closure that receives parameters (mixed[] $data) and returns mixed[]
     */
    public function setEndMerge(Closure $endMerge = null)
    {
        $this->endMerge = $endMerge;
    }


    /**
     * Set a Hook to be called before the import of a row
     *
     * @param Closure $beforeRowHook
     */
    public function setBeforeRowHook(Closure $beforeRowHook = null)
    {
        $this->beforeRowHook = $beforeRowHook;
    }

    /**
     * Set a Hook to be called after the import of a row
     *
     * @param Closure $afterRowHook
     */
    public function setAfterRowHook(Closure $afterRowHook = null)
    {
        $this->afterRowHook = $afterRowHook;
    }

    /**
     * Function to be executed after import of a given type has been completed
     *
     * @param Closure|null $afterImportCompleted
     */
    public function setAfterImportCompleted(Closure $afterImportCompleted = null)
    {
        $this->afterImportCompleted = $afterImportCompleted;
    }

    /**
     * Set the import data that will be used instead of querying the database
     *
     * @param Builder|null $query
     */
    public function setQuery(Builder $query = null)
    {
        $this->query = $query;
    }

    /**
     * Get essential information from old system,
     * but run a function to use the Updaters
     * to get the rest of the information from the LibeoDataService
     *
     * @param string $table
     * @param string $identifier
     * @param string $updateFunction
     * @param array  $select
     */
    public function runImportQuery($table, $identifier, $updateFunction, array $select = ['id', 'created_at'])
    {
        $select[] = $identifier;

        $query = $this->db
            ->table($table)
            ->select($select)
            ->orderBy('id');

        $this->table = $table;

        DB::connection()->disableQueryLog();
        $results = $query->get();
        $processed = 1;

        $this->createProgressBar($query->count());
        foreach ($results as $result) {
            try {
                $updateFunction($result);
            } catch (SkipRowImportException $error) {
                $this->printSkipRowExceptionMessage($error);
            }
            $this->progressBar->setProgress($processed);
            $processed++;
        }

        $this->table = null;
        DB::connection()->enableQueryLog();
        $this->finishTask();
    }

    public function runOnModel(EloquentBuilder $model, Closure $callback)
    {
        $items = $model->get();
        $processed = 1;

        DB::connection()->disableQueryLog();

        $this->createProgressBar($items->count());
        foreach ($items as $item) {
            $callback($item);
            $this->progressBar->setProgress($processed);
            $processed++;
        }

        DB::connection()->enableQueryLog();

        $this->finishTask();
    }

    /**
     * Execute the import runner.
     */
    public function run()
    {
        if ($this->query) {
            $query = $this->query;
        } else {
            $query = $this->db->table($this->sourceTable)->select('*')->orderBy('id');
        }

        foreach ($this->selectConditions as $column => $data) {
            list($operator, $value) = $data;
            $query->where($column, $operator, $value);
        }

        $count = $query->count();
        if ($this->query) {
            $count = count($this->query->get());
        }

        $this->createProgressBar($count);

        $page = 0;

        // Logging so much queries will fill memory and add CPU-work.
        DB::connection()->disableQueryLog();

        $processed = 1;

        $lastProgressUpdate = (microtime(true) * 1000);

        // Recover source data in chunks to balance query-count and memory-usage
        while ($clockingValues = $query->forPage(++$page, self::DATABASE_FETCH_BLOCK_SIZE)->get()) {
            foreach ($clockingValues as $clockingValue) {
                /**
                 * @var stdClass $clockingValue
                 */
                $this->importValue($clockingValue);
                if ((microtime(true) * 1000) - $lastProgressUpdate > self::UPDATE_PROGRESS_RATE) {
                    $this->progressBar->setProgress($processed);
                    $lastProgressUpdate = (microtime(true) * 1000);
                }
                $processed++;
            }
        }
        DB::connection()->enableQueryLog();

        $this->finishTask();

        if ($this->afterImportCompleted !== null) {
            $call = $this->afterImportCompleted;
            $call();
        }
    }

    /**
     * Create a new instance of the progress bar.
     *
     * @param int $max
     *
     * @return ProgressBar
     */
    protected function createProgressBar($max)
    {
        $this->output("\n");

        $format = "  - Importing <info>{$this->table}</info>: <comment>%current%/%max%</comment>";
        $format .= " [<comment>%percent%%</comment>]\n    %remaining% %memory:6s%";

        $progressBar = new ProgressBar($this->output, $max);
        $progressBar->setFormat($format);
        $progressBar->start();

        $this->progressBar = $progressBar;
    }

    /**
     * Output given text in the console.
     *
     * @param $text
     */
    protected function output($text)
    {
        $this->output->writeln($text);
    }

    /**
     * End the current task by marking the progress bar as finished.
     */
    protected function finishTask()
    {
        $this->progressBar->finish();
        $this->progressBar = null;
    }

    /**
     * Map the relations
     * Uses provided data and place values within new structure.
     * For every key
     *
     * @param stdClass $data
     * @param string[] $keys
     *
     * @return mixed[]
     */
    protected function mapRelations(stdClass $data, array $keys)
    {
        $relations = [];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->columns)) {
                continue;
            }

            $dataValue = $this->columns[$key];
            $relations[$key] = $data->{$dataValue};
        }

        return $relations;
    }

    /**
     * @param SkipRowImportException $error
     */
    protected function printSkipRowExceptionMessage(SkipRowImportException $error)
    {
        if (!$error->getSilent()) {
            $this->output("\n");
            $this->output(
                "<comment>Skipping row of type \"{$error->getRowType()}\" for reason : {$error->getMessage()}</comment>"
            );
            $this->output("\n");
        }
    }

    /**
     * Import a single value from old system into new System
     *
     * @param stdClass $clockingRow
     *
     * @throws ImportFailedException
     */
    protected function importValue(stdClass $clockingRow)
    {
        try {
            $keys = $this->mapRelations($clockingRow, $this->keys);
            $data = $this->mapRelations($clockingRow, array_keys($this->columns));

            if ($this->endMerge !== null) {
                $call = $this->endMerge;
                $data = array_merge($data, $call($data));
            }

            //If hook is present run action before inserting row
            if ($this->beforeRowHook !== null) {
                $call = $this->beforeRowHook;
                $call($data);
            }

            // Generate new model from factory
            /** @var EloquentBuilder $model */
            $factory = $this->modelFactory;
            $model = $factory();

            Model::unguard();
            // Will update existing row if "keys" data matches.
            // Otherwise, create a new entry
            if (DB::table($model->getTable())->where($keys)->exists()) {
                DB::table($model->getTable())->where($keys)->update($data);
            } else {
                DB::table($model->getTable())->insert(array_merge([
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ], $keys, $data));
            }
            Model::reguard();

            //If hook is present run action after row has been inserted
            if ($this->afterRowHook !== null) {
                $call = $this->afterRowHook;
                $call($data, $model);
            }
        } catch (SkipRowImportException $e) {
            $this->printSkipRowExceptionMessage($e);
        } catch (\Exception $e) {
            $this->output("\n");
            $this->output("<error>Importation failed</error>: {$e->getMessage()}");
            $this->output('Additional details:');
            $this->output(print_r($clockingRow, true));
            $this->output($e->getTraceAsString());

            throw new ImportFailedException();
        }
    }
}
