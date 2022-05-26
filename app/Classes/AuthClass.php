<?php

namespace App\Classes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class AuthClass
{
    const STATUS_TRUE = 't';
    const STATUS_FALSE = 'f';
    const STATUS_AWAIT = 'a';
    const STATUS_CLEAR = 'c';

    private const TABLE_NAME = 'tr_account';

    /**
     * @param $params
     * @return int
     */
    public static function create($params):int
    {
        try {
            return DB::table(self::TABLE_NAME)->insertGetId($params);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param $where string
     * @param $params string
     * @return object | false  | null
     */
    public static function findOne(string $where, string $params): object|bool|null
    {
        try {
            return DB::table(self::TABLE_NAME)->where($where, $params)->first();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param $params array
     * @return bool|int
     */
    public static function update(array $params): bool|int
    {
        try {
            return DB::table(self::TABLE_NAME)->where('no', Auth::user()->no)->update($params);
        } catch (Exception $e) {
            return false;
        }
    }
}
