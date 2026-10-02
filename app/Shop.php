<?php 
namespace VanguardLTE
{
    class Shop extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'shops';
        protected $fillable = [
            'name', 
            'balance', 
            'percent', 
            'max_win', 
            'frontend', 
            // Host -> shop mapping. May contain multiple domains separated by commas.
            'domain', 
            'registration_open', 
            'open_from', 
            'open_to', 
            'closed_days', 
            'timezone', 
            'min_deposit', 
            'max_deposit', 
            'min_withdraw', 
            'max_withdraw', 
            'password',
            'currency', 
            'shop_limit', 
            'is_blocked', 
            'orderby', 
            'user_id', 
            'pending', 
            'access', 
            'country', 
            'os', 
            'device', 
            'rules_terms_and_conditions', 
            'rules_privacy_policy', 
            'rules_general_bonus_policy', 
            'rules_why_bitcoin', 
            'rules_responsible_gaming', 
            'happyhours_active', 
            'progress_active', 
            'invite_active', 
            'welcome_bonuses_active', 
            'sms_bonuses_active', 
            'wheelfortune_active'
        ];
        public static $values = [
            // The currency list is no longer defined here. The single source
            // of truth is VanguardLTE\Support\Currency; the value is populated
            // below the class (static PHP properties cannot call functions).
            // The list is deliberately COMPLETE — including retired currencies
            // such as HRK/CFA and crypto units — because it feeds the `in:`
            // validation rule; legacy shops must remain re-saveable.
            // For the list OFFERED in the form, use Currency::options().
            'currency' => [], 
            // The game engine maps shop->percent into three buckets via
            // Game::$values['random_keys']: 74-80, 82-88, 90-96. Values outside
            // those silently fall back to '90_96' — so percent 100 is quietly
            // played as 90-96. This list contains every value that actually
            // maps to a bucket, not just four.
            'percent' => [
                74, 75, 76, 77, 78, 79, 80,
                82, 83, 84, 85, 86, 87, 88,
                90, 91, 92, 93, 94, 95, 96,
            ], 
            'orderby' => [ 
                'RTP'
            ], 
            'max_win' => [
                50, 
                100, 
                200, 
                300, 
                400, 
                500, 
                1000, 
                2000, 
                3000, 
                4000, 
                5000, 
                10000, 
                50000, 
                100000
            ], 
            'shop_limit' => [
                100, 
                200, 
                300, 
                400, 
                500, 
                1000, 
                10000, 
                100000
            ], 
            // Populated below the class from 'percent' — the labels name the RTP
            // buckets the engine actually uses, not made-up ranges. The old
            // labels ('90 - 92' for percent 90) did not match the 90-96 bucket.
            'percent_labels' => []
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
            self::saved(function($model)
            {
                Shop::where('id', $model->id)->update(['name' => Lib\Functions::remove_emoji($model->name)]);
                event(new Events\Shop\ShopEdited($model));
            });
            self::deleting(function($model)
            {
                // 28 child tables are deleted one by one. Without a transaction,
                // a mid-way failure (deadlock, timeout, dropped connection) leaves
                // a shop with half-deleted data that cannot be recovered.
                // Nested transactions are safe: Laravel uses savepoints when the
                // caller has already opened its own transaction.
                \Illuminate\Support\Facades\DB::transaction(function () use ($model) {
                // Game was NOT in this list before, so every deleted shop left
                // ~1,300 orphaned game rows forever — found while testing
                // Clone Shop: deleting a cloned shop left all 1,315 of its rows
                // behind. This is also what kept the games table growing in
                // installations that frequently create and delete shops.
                // game_categories is deliberately untouched: the table has no
                // shop_id and is keyed on original_id, so it is shared by all
                // shops. Deleting it here would strip the same game categories
                // from other shops.
                Game::where('shop_id', $model->id)->delete();
                StatGame::where('shop_id', $model->id)->delete();
                Category::where('shop_id', $model->id)->delete();
                OpenShift::where('shop_id', $model->id)->delete();
                ShopUser::where('shop_id', $model->id)->delete();
                Statistic::where('shop_id', $model->id)->delete();
                StatisticAdd::where('shop_id', $model->id)->delete();
                Api::where('shop_id', $model->id)->delete();
                ShopCategory::where('shop_id', $model->id)->delete();
                JPG::where('shop_id', $model->id)->delete();
                Pincode::where('shop_id', $model->id)->delete();
                HappyHour::where('shop_id', $model->id)->delete();
                GameBank::where('shop_id', $model->id)->delete();
                FishBank::where('shop_id', $model->id)->delete();
                Invite::where('shop_id', $model->id)->delete();
                WheelFortune::where('shop_id', $model->id)->delete();
                ShopCountry::where('shop_id', $model->id)->delete();
                ShopOS::where('shop_id', $model->id)->delete();
                ShopDevice::where('shop_id', $model->id)->delete();
                Progress::where('shop_id', $model->id)->delete();
                WelcomeBonus::where('shop_id', $model->id)->delete();
                SMSBonus::where('shop_id', $model->id)->delete();
                SMSBonusItem::where('shop_id', $model->id)->delete();
                Reward::where('shop_id', $model->id)->delete();
                Ticket::where('shop_id', $model->id)->delete();
                TicketAnswer::where('shop_id', $model->id)->delete();
                Security::where('shop_id', $model->id)->delete();
                UserActivity::where('shop_id', $model->id)->delete();
                });
            });
        }
        public function get_values($key, $add_empty = false, $add_value = false)
        {
            $keys = Shop::$values[$key];
            $labels = $keys;
            if( $add_empty ) 
            {
                $combined = array_combine(array_merge([''], $keys), array_merge(['---'], $labels));
            }
            else
            {
                $combined = array_combine($keys, $labels);
            }
            if( $add_value ) 
            {
                return [$add_value => $add_value] + $combined;
            }
            return $combined;
        }
        /** Jam operasional; kosong = buka 24/7. Blokir manual tetap menang. */
        public function isOpenNow()
        {
            return \VanguardLTE\Support\ShopSchedule::isOpen($this);
        }
        public function scheduleLabel()
        {
            return \VanguardLTE\Support\ShopSchedule::describe($this);
        }
        /** Batas setor/tarik shop; null = ikut setelan global. */
        public function limit($key)
        {
            $value = $this->{$key} ?? null;
            return ($value === null || $value === '') ? null : (float) $value;
        }
        public function get_percent_label($percent = false)
        {
            if( !$percent ) 
            {
                $percent = $this->percent;
            }
            if( isset(Shop::$values['percent_labels'][$percent]) )
            {
                return Shop::$values['percent_labels'][$percent];
            }
            // The old fallback pointed at key 96, but percent_labels only holds
            // 90/84/82/74 — so /backend/game always 500'd ("Undefined array key 96")
            // for shops with a percent outside that list (e.g. percent = 100).
            // Show the value as-is; do not invent a range label.
            return (string) $percent;
        }
        public function distributors_count()
        {
            $memberIds = ShopUser::where('shop_id', $this->id)->pluck('user_id');
            if( count($memberIds) ) 
            {
                return User::whereIn('id', $memberIds)->whereIn('role_id', [
                    4, 
                    5
                ])->count();
            }
            return 0;
        }
        public function getUsersByRole($role)
        {
            $role = Role::where('slug', $role)->first();
            $memberIds = ShopUser::where('shop_id', $this->id)->groupBy('user_id')->pluck('user_id');
            if( $memberIds ) 
            {
                return User::where('role_id', $role->id)->whereIn('id', $memberIds)->get();
            }
            return User::where('id', 0)->get();
        }
        public function getRowspan()
        {
            $cashierCount = User::where([
                'shop_id' => $this->id, 
                'role_id' => 2
            ])->count();
            return ($cashierCount > 0 ? $cashierCount : 1);
        }
        public function categories()
        {
            return $this->hasMany('VanguardLTE\ShopCategory', 'shop_id');
        }
        public function users()
        {
            return $this->hasMany('VanguardLTE\ShopUser');
        }
        public function creator()
        {
            return $this->hasOne('VanguardLTE\User', 'id', 'user_id');
        }
        public function countries()
        {
            return $this->hasMany('VanguardLTE\ShopCountry');
        }
        public function oss()
        {
            return $this->hasMany('VanguardLTE\ShopOS');
        }
        public function devices()
        {
            return $this->hasMany('VanguardLTE\ShopDevice');
        }
        public function titles()
        {
            $titles = [];
            if( $this->categories ) 
            {
                foreach( $this->categories as $category ) 
                {
                    $titles[] = $category->category->title;
                }
            }
            return implode(', ', $titles);
        }
        public function blocked()
        {
            if( settings('siteisclosed') ) 
            {
                return true;
            }
            $parent = User::find($this->creator->id)->first();
            if( $parent->is_blocked ) 
            {
                return true;
            }
            if( $this->is_blocked ) 
            {
                return true;
            }
            return false;
        }
        public function hasActiveRules()
        {
            $rules = Rule::get();
            if( count($rules) ) 
            {
                foreach( $rules as $rule ) 
                {
                    if( $this->{'rules_' . $rule->href} ) 
                    {
                        return true;
                    }
                }
            }
            return false;
        }
        public function getBonusesList()
        {
            $bonuses = [];
            if( $this->welcome_bonuses_active ) 
            {
                $welcomeBonuses = WelcomeBonus::where(['shop_id' => $this->id])->get();
                if( count($welcomeBonuses) ) 
                {
                    foreach( $welcomeBonuses as $welcomeBonus ) 
                    {
                        $bonuses[] = [
                            'type' => 'welcome_bonus', 
                            'is_first' => 0, 
                            'data' => $welcomeBonus
                        ];
                    }
                }
            }
            if( $this->happyhours_active ) 
            {
                $happyhour = HappyHour::where([
                    'shop_id' => $this->id, 
                    'time' => date('G')
                ])->first();
                if( !$happyhour ) 
                {
                    for( $hour = date('G'); $hour < 24; $hour++ ) 
                    {
                        $happyhour = HappyHour::where([
                            'shop_id' => $this->id, 
                            'time' => $hour
                        ])->first();
                        if( $happyhour ) 
                        {
                            break;
                        }
                    }
                }
                if( !$happyhour ) 
                {
                    for( $hour = 0; $hour < date('G'); $hour++ ) 
                    {
                        $happyhour = HappyHour::where([
                            'shop_id' => $this->id, 
                            'time' => $hour
                        ])->first();
                        if( $happyhour ) 
                        {
                            break;
                        }
                    }
                }
                if( $happyhour ) 
                {
                    $bonuses[] = [
                        'type' => 'happyhour', 
                        'is_first' => 0, 
                        'data' => $happyhour
                    ];
                }
            }
            if( $this->progress_active ) 
            {
                $userRating = auth()->check() ? auth()->user()->rating : 0;
                $progress = Progress::where([
                    'rating' => $userRating + 1, 
                    'shop_id' => $this->id
                ])->first();
                if( $progress ) 
                {
                    $bonuses[] = [
                        'type' => 'progress', 
                        'is_first' => 0, 
                        'data' => $progress
                    ];
                }
            }
            if( $this->invite_active ) 
            {
                $invite = Invite::where(['shop_id' => $this->id])->first();
                if( $invite ) 
                {
                    $bonuses[] = [
                        'type' => 'invite', 
                        'is_first' => 0, 
                        'data' => $invite
                    ];
                }
            }
            if( $this->sms_bonuses_active ) 
            {
                $smsBonuses = SMSBonus::where(['shop_id' => $this->id])->get();
                if( count($smsBonuses) ) 
                {
                    $data = [];
                    foreach( $smsBonuses as $smsbonus ) 
                    {
                        $data[] = $smsbonus;
                    }
                    $bonuses[] = [
                        'type' => 'sms_bonus', 
                        'is_first' => 0, 
                        'data' => $data
                    ];
                }
            }
            if( isset($bonuses[0]) ) 
            {
                $bonuses[0]['is_first'] = 1;
            }
            return $bonuses;
        }
    }

    // Runs once when the Shop class is autoloaded. Currency has no framework
    // dependencies, so this is safe even if the class loads before boot finishes.
    Shop::$values['currency'] = \VanguardLTE\Support\Currency::validationCodes();

    // Percent labels name the RTP buckets the game engine actually uses
    // (Game::$values['random_keys']), so operators know 84 and 88 land in the
    // same bucket.
    foreach (Shop::$values['percent'] as $shopPercentValue) {
        $band = $shopPercentValue >= 90 ? '90-96' : ($shopPercentValue >= 82 ? '82-88' : '74-80');
        Shop::$values['percent_labels'][(string) $shopPercentValue] = $shopPercentValue . '  (RTP ' . $band . ')';
    }
    unset($shopPercentValue, $band);

}
