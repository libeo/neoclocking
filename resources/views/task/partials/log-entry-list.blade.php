<div class="clock-history-list">
    @foreach ($logEntries as $logEntry)
        <div class="clock-wrapper is-clock-history">
            <div class="clock-left">
                <img src="{{ $logEntry->user->gravatar() }}" alt="{{ $logEntry->user->present()->fullName() }}" class="clock-profile-picture">
            </div>
            <div class="clock-middle">
                <div class="clock-author">
                    <span class="clock-author-label">Temps ajouté par</span>
                    <span class="clock-author-name">{{ $logEntry->user->present()->fullName() }}</span>
                </div>
            </div>
            <div class="clock-right">
                <span class="clock-date">{{ $logEntry->started_at->setTimezone('America/Montreal')->format('d / m / Y') }}</span>
                        <span class="clock-hour">
                            <span class="clock-hour-start">{{ $logEntry->started_at->setTimezone('America/Montreal')->format('H:i') }}</span>
                            <span class="clock-hour-end">{{ isset($logEntry->ended_at) ? $logEntry->ended_at->setTimezone('America/Montreal')->format('H:i') : ''}}</span>
                        </span>
                <span class="clock-hour-total">{{ $logEntry->present()->duration() }}</span>
                <div class="clock-description">{{ $logEntry->comment }}</div>
            </div>
        </div>
    @endforeach
    <div class="pagination-wrapper">
        {!! $logEntries->render() !!}
    </div>
</div>