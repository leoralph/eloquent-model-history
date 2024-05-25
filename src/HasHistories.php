<?php

namespace LeoRalph\History;

trait HasHistories
{
    private ?string $historyOperation = null;
    private ?string $historyDescription = null;

    public function withHistoryOperation(string $operation): static
    {
        $this->historyOperation = $operation;

        return $this;
    }

    public function withHistoryDescription(string $description): static
    {
        $this->historyDescription = $description;

        return $this;
    }

    /**
     * Get all of the model's histories.
     */
    public function histories()
    {
        return $this->morphMany(History::class, 'model');
    }

    /**
     * Get all of the model's histories.
     *
     * @return void
     */
    public static function bootHasHistories()
    {
        if (!config('history.enabled')) {
            return;
        }

        if (in_array(app()->environment(), config('history.env_blacklist'))) {
            return;
        }

        if (app()->runningInConsole() && !config('history.console_enabled')) {
            return;
        }

        if (app()->runningUnitTests() && !config('history.test_enabled')) {
            return;
        }

        static::observe(HistoryObserver::class);
    }

    /**
     * Get the model's meta in history.
     *
     * @return array
     */
    public function getModelMeta($event)
    {
        switch ($event) {
            case 'updating':
                /*
                 * Gets the model's altered values and tracks what had changed
                 */
                $changes = $this->getDirty();

                $changed = [];
                foreach ($changes as $key => $value) {
                    if (static::isIgnored($this, $key))
                        continue;

                    array_push($changed, ['key' => $key, 'old' => $this->getOriginal($key), 'new' => $this->$key]);
                }
                return $changed;
            case 'created':
            case 'deleting':
            case 'restored':
                return null;
        }
    }

    public static function isIgnored($model, $key)
    {
        $blacklist = config('history.attributes_blacklist');
        $name = get_class($model);
        $array = isset($blacklist[$name]) ? $blacklist[$name] : null;
        return !empty($array) && in_array($key, $array);
    }

    public function createHistory(
        ?string $operation = null,
        array $previous = [],
        array $changes = [],
    ): History {
        $user = auth()->user();

        [$userId, $userType] = $user
            ? [$user->id, get_class($user)]
            : [null, null];

        $operation = $operation ?? $this->historyOperation;

        if (empty($operation)) {
            throw new \Exception('Trying to create a history without an operation.');
        }

        $history = $this->histories()->create([
            'operation' => $operation,
            'description' => $this->historyDescription,
            'previous' => $previous,
            'changes' => $changes,
            'user_id' => $userId,
            'user_type' => $userType,
            'performed_at' => now(),
        ]);

        $this->historyOperation = null;
        $this->historyDescription = null;

        return $history;
    }
}
