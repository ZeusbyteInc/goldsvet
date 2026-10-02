<?php 
namespace VanguardLTE
{
    class StatGame extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'stat_game';
        protected $fillable = [
            'date_time', 
            'user_id', 
            'balance', 
            'bet', 
            'win', 
            'game', 
            'denomination', 
            'in_game', 
            'in_jpg', 
            'in_profit', 
            'game_bank', 
            'jack_balance', 
            'shop_id', 
            'slots_bank', 
            'bonus_bank', 
            'fish_bank', 
            'table_bank', 
            'little_bank', 
            'total_bank'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
            self::created(function($model)
            {
                app(\VanguardLTE\Services\RealtimeSyncService::class)->emitFinanceRefresh($model->user_id, 'game_stat_created');
            });
            self::updated(function($model)
            {
                app(\VanguardLTE\Services\RealtimeSyncService::class)->emitFinanceRefresh($model->user_id, 'game_stat_updated');
            });
            self::deleted(function($model)
            {
                app(\VanguardLTE\Services\RealtimeSyncService::class)->emitFinanceRefresh($model->user_id, 'game_stat_deleted');
            });
        }
        // Daily Win Limit: every recorded win eats into the shop's budget for
        // today. booted() (not another hook inside boot()) keeps this concern
        // separate from the RealtimeSync observers above.
        //
        // The raw array (NOT ->win and NOT getRawOriginal) is deliberate: the
        // getWinAttribute() accessor formats the value with a thousands
        // separator ("1 500.5000") and a float cast of that string stops at
        // the space (1500.5 counted as 1), while getRawOriginal() is still
        // NULL at created-time — `original` is only synced after save.
        protected static function booted()
        {
            static::created(function($stat)
            {
                try
                {
                    $attrs = $stat->getAttributes();
                    $win = isset($attrs['win']) ? (float) $attrs['win'] : 0.0;
                    if( $win > 0 && isset($attrs['shop_id']) )
                    {
                        \VanguardLTE\DailyWinBudget::addSpent((int) $attrs['shop_id'], $win);
                    }

                    // Turnover ladder (count_*): remaining wagering before
                    // withdrawals open. Previously ONLY the 205 Pragmatic games
                    // decremented it themselves (PragmaticLib/SwitchMoney) —
                    // ~1,100 legacy slots, PGSoft, and all fish/arcade titles
                    // never did, so the bonus turnover_multiplier never went
                    // down for non-Pragmatic players and the legacy
                    // count_welcomebonus withdrawal block had to be commented
                    // out. Here it is decremented based on TOTAL BET; Pragmatic
                    // games are skipped because they keep their own books
                    // (bonus-money semantics) to avoid double counting.
                    //
                    // Free-spin rows must NOT decrement: free spins are not
                    // the player's money, and the row's bet column carries the
                    // triggering total bet repeated per row (one FS round of
                    // 15 spins x full bet = a 15x over-decrement — see session
                    // 13.180 "bet" from a single round, 2026-09-21). Two suffix
                    // conventions exist on this platform:
                    //   " FS" — 208 Pragmatic engines (PragmaticLib/Statistic)
                    //   " FG" — 1,312 legacy engines (SlotSettings::SaveLogReport;
                    //           " DG" = real-money gamble, still counted)
                    // The triggering spin is recorded on a plain row with the
                    // original bet, so turnover is counted exactly once.
                    $gameName = (string) (isset($attrs['game']) ? $attrs['game'] : '');
                    $nameTail = substr($gameName, -3);
                    $isFreeSpin = $nameTail === ' FS' || $nameTail === ' FG';
                    $bet = $isFreeSpin ? 0.0 : (isset($attrs['bet']) ? (float) $attrs['bet'] : 0.0);
                    $user = null;
                    if( $bet > 0 && !empty($attrs['user_id']) && !empty($attrs['game']) )
                    {
                        $user = \VanguardLTE\User::find((int) $attrs['user_id']);
                    }

                    // Deposit turnover (1x): applies to ALL games — the Pragmatic
                    // engine does not manage count_deposit (only their SwitchMoney
                    // bonus ladder), so it is safe to decrement here with no
                    // double-counting risk.
                    if( $user && (float) $user->count_deposit > 0 )
                    {
                        $user->decrement('count_deposit', min((float) $user->count_deposit, $bet));
                    }

                    if( $user
                        && !is_dir(app_path('Games/' . $attrs['game'] . '/PragmaticLib')) )
                    {
                        {
                            $remaining = $bet;
                            foreach( ['count_tournaments', 'count_happyhours', 'count_refunds', 'count_progress', 'count_daily_entries', 'count_invite', 'count_welcomebonus', 'count_firstdep', 'count_smsbonus', 'count_wheelfortune'] as $field )
                            {
                                if( $remaining <= 0 )
                                {
                                    break;
                                }
                                $value = (float) $user->{$field};
                                if( $value > 0 )
                                {
                                    $take = min($value, $remaining);
                                    $user->decrement($field, $take);
                                    $remaining -= $take;
                                }
                            }
                        }
                    }
                }
                catch( \Throwable $e )
                {
                    // Statistics must never fail a spin.
                }
            });
        }
        public function getSlotsBankAttribute($value)
        {
            return number_format($value, 2, '.', ' ');
        }
        public function getBonusBankAttribute($value)
        {
            return number_format($value, 2, '.', ' ');
        }
        public function getFishBankAttribute($value)
        {
            return number_format($value, 2, '.', ' ');
        }
        public function getTableBankAttribute($value)
        {
            return number_format($value, 2, '.', ' ');
        }
        public function getLittleBankAttribute($value)
        {
            return number_format($value, 2, '.', ' ');
        }
        public function getTotalBankAttribute($value)
        {
            return number_format($value, 2, '.', ' ');
        }
        public function getBalanceAttribute($value)
        {
            return number_format($value, 4, '.', ' ');
        }
        public function getBetAttribute($value)
        {
            return number_format($value, 4, '.', ' ');
        }
        public function getWinAttribute($value)
        {
            return number_format($value, 4, '.', ' ');
        }
        public function getInGameAttribute($value)
        {
            return number_format($value, 4, '.', ' ');
        }
        public function getInJpgAttribute($value)
        {
            return number_format($value, 4, '.', ' ');
        }
        public function getInProfitAttribute($value)
        {
            return number_format($value, 4, '.', ' ');
        }
        public function user()
        {
            return $this->belongsTo('VanguardLTE\User', 'user_id');
        }
        public function shop()
        {
            return $this->belongsTo('VanguardLTE\Shop');
        }
        public function game_item()
        {
            return $this->hasOne('VanguardLTE\Game', 'name', 'game');
        }
        public function name_ico()
        {
            return explode(' ', $this->game)[0];
        }
        public static function get_user_rtp($id, $limit = 250)
        {
            $user_query = \DB::select("select sum(bet) as bets,sum(win) as wins from (select bet,win from w_stat_game WHERE user_id = ".$id." order by w_stat_game.date_time desc limit ".$limit.") as final");
			if(isset($user_query[0])){
				$bets = (float) $user_query[0]->bets; if( $bets <= 0 ) return false; return (float) $user_query[0]->wins / $bets * 100;
			} else {
				return false;
			}
      }
    }

}
