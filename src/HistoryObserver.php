<?php

namespace LeoRalph\History;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class HistoryObserver
{
    /**
     * Listen to the Model created event.
     *
     * @param  mixed $model
     * @return void
     */
    public function created(Model $model): void
    {
        if (!static::filter('created'))
            return;

        $model->createHistory(
            trans('panoscape::history.created'),
        );
    }

    /**
     * Listen to the Model updating event.
     *
     * @param  mixed $model
     * @return void
     */
    public function updating(Model $model)
    {
        if (!static::filter('updating'))
            return;

        /*
         * Gets the model's altered values and tracks what had changed
         */
        $changes = $model->getDirty();
        /**
         * Bypass restoring event
         */
        if (array_key_exists('deleted_at', $changes))
            return;

        $previous = array_intersect_key($model->getRawOriginal(), $changes);
        /**
         * Bypass updating event when meta is empty
         */
        if (!$changes)
            return;

        $model->createHistory(
            trans('panoscape::history.updating'),
            $previous,
            $changes,
        );
    }

    /**
     * Listen to the Model deleting event.
     *
     * @param  mixed $model
     * @return void
     */
    public function deleting($model)
    {
        if (!static::filter('deleting'))
            return;

        $model->createHistory(
            trans('panoscape::history.deleting'),
        );
    }

    /**
     * Listen to the Model restored event.
     *
     * @param  mixed $model
     * @return void
     */
    public function restored($model)
    {
        if (!static::filter('restored'))
            return;

        $model->createHistory(
            trans('panoscape::history.restored'),
        );
    }

    public static function getModelName($model)
    {
        $class = class_basename($model);
        $key = 'panoscape::history.models.' . Str::snake($class);
        $value = trans($key);

        return $key == $value ? $class : $value;
    }

    public static function getUserID()
    {
        return static::getAuth()->check() ? static::getAuth()->user()->id : null;
    }

    public static function getUserType()
    {
        return static::getAuth()->check() ? get_class(static::getAuth()->user()) : null;
    }

    public static function filter($action)
    {
        if (!static::getAuth()->check()) {
            if (in_array('nobody', config('history.user_blacklist'))) {
                return false;
            }
        } elseif (in_array(get_class(static::getAuth()->user()), config('history.user_blacklist'))) {
            return false;
        }

        return is_null($action) || in_array($action, config('history.events_whitelist'));
    }

    private static function getAuth()
    {
        $guards = config('history.auth_guards');
        if (is_bool($guards) && $guards == true)
            return auth(static::activeGuard());
        if (is_array($guards)) {
            foreach ($guards as $guard)
                if (auth($guard)->check())
                    return auth($guard);
        }
        return auth();
    }

    private static function activeGuard()
    {
        foreach (array_keys(config('auth.guards')) as $guard) {
            if (auth($guard)->check())
                return $guard;
        }
        return null;
    }

}
