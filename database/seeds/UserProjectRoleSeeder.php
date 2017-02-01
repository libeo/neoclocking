<?php

use Illuminate\Database\Seeder;
use NeoClocking\Models\UserProjectRole;
use NeoClocking\Models\UserRole;
use NeoClocking\Repositories\ProjectRepository;
use NeoClocking\Repositories\UserRepository;
use NeoClocking\Repositories\UserRoleRepository;

class UserProjectRoleSeeder extends Seeder
{

    public function run()
    {
        $userRepository = new UserRepository();
        $projectRepository = new ProjectRepository();
        $userRoleRepository = new UserRoleRepository();

        $user = $userRepository->findOneByUsername('test');
        $project = $projectRepository->findOneByNumberOrFail('P-99999-01');
        $memberRole = $userRoleRepository->findOneByCode(UserRole::CODE_MEMBER);
        $managerRole = $userRoleRepository->findOneByCode(UserRole::CODE_MANAGER);

        UserProjectRole::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'user_role_id' => $memberRole->id,
        ]);

        UserProjectRole::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'user_role_id' => $memberRole->id,
        ]);

        UserProjectRole::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'user_role_id' => $managerRole->id,
        ]);
    }
}
