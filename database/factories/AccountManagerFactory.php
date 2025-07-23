<?php

namespace Database\Factories;

use App\Models\AccountManager;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * AccountManager 模型工厂
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccountManager>
 */
class AccountManagerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AccountManager::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $email = fake()->unique()->safeEmail();

        return [
            'account_id' => Account::factory(),

            // 基本信息
            'apple_id_display' => $email,
            'name' => [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'middleName' => fake()->optional(0.3)->firstName(),
                'displayName' => "{$firstName} {$lastName}",
                'givenName' => $firstName,
                'familyName' => $lastName,
            ],
            'localized_birthday' => fake()->date('Y-m-d'),
            'primary_email_address' => [
                'address' => $email,
                'verified' => fake()->boolean(90),
                'primary' => true,
                'createdDate' => fake()->dateTimeBetween('-2 years')->format('c'),
            ],

            // 账户状态布尔值
            'is_paid_account' => fake()->boolean(60),
            'is_hsa_eligible' => fake()->boolean(70),
            'is_hsa' => fake()->boolean(50),

            // 安全特性布尔值
            'show_hsa2_recovery_key_section' => fake()->boolean(80),
            'should_show_data_recovery_service_ui' => fake()->boolean(70),
            'should_show_recovery_key_ui' => fake()->boolean(75),

            // 验证状态布尔值
            'exceeded_verification_attempts' => fake()->boolean(5), // 很少超过
            'scnt_required' => fake()->boolean(20),

            // UI显示控制布尔值
            'enable_right_to_left_display' => fake()->boolean(10),
            'login_handle_available' => fake()->boolean(85),
            'is_apple_id_and_primary_email_same' => fake()->boolean(70),
            'should_show_beneficiary_ui' => fake()->boolean(30),
            'show_npa' => fake()->boolean(40),
            'is_account_name_editable' => fake()->boolean(80),
            'should_show_custodian_ui' => fake()->boolean(25),
            'show_data_recovery_service_ui' => fake()->boolean(60),

            // 名称相关
            'name_order' => fake()->randomElement(['first_last', 'last_first']),
            'pronounce_names_required' => fake()->boolean(20),
            'middle_name_required' => fake()->boolean(30),
            'person_name_order' => fake()->randomElement(['western', 'eastern']),
            'no_space_required_in_name' => fake()->boolean(15),
            'use_person_name_in_messaging_max_length' => fake()->numberBetween(20, 100),

            // 邮箱相关
            'rescue_email_exists' => fake()->boolean(60),
            'alternate_email_addresses' => fake()->randomElements([
                fake()->email(),
                fake()->email(),
                fake()->email(),
            ], fake()->numberBetween(0, 2)),
            'should_allow_add_alternate_email' => fake()->boolean(80),
            'hide_my_email_count' => fake()->numberBetween(0, 10),

            // 系统配置
            'non_fteu_enabled' => fake()->boolean(70),
            'is_redesign_sign_in_enabled' => fake()->boolean(80),
            'environment' => fake()->randomElement(['production', 'staging', 'development']),
            'obfuscate_birthday' => fake()->boolean(30),

            // 复杂JSON对象
            'country_features' => [
                'countryCode' => fake()->countryCode(),
                'supportedFeatures' => fake()->randomElements([
                    'two_factor_auth',
                    'family_sharing',
                    'icloud_backup',
                    'apple_pay',
                    'app_store',
                    'itunes_store'
                ], fake()->numberBetween(2, 4)),
                'restrictions' => fake()->randomElements([
                    'age_verification',
                    'payment_methods',
                    'content_rating'
                ], fake()->numberBetween(0, 2)),
            ],

            'api_key' => fake()->sha256(),

            'page_features' => [
                'showAdvancedSecurity' => fake()->boolean(),
                'enableDataRecovery' => fake()->boolean(),
                'showFamilySettings' => fake()->boolean(),
                'enableAccountRecovery' => fake()->boolean(),
            ],

            'add_alternate_email' => [
                'enabled' => fake()->boolean(80),
                'maxCount' => fake()->numberBetween(1, 5),
                'verificationRequired' => fake()->boolean(90),
            ],

            'display_name' => [
                'showFirstName' => fake()->boolean(90),
                'showLastName' => fake()->boolean(85),
                'showMiddleName' => fake()->boolean(30),
                'format' => fake()->randomElement(['full', 'first_last', 'initials']),
            ],

            'apple_id' => [
                'appleId' => $email,
                'dsid' => fake()->numerify('##########'),
                'accountType' => fake()->randomElement(['individual', 'family', 'business']),
                'createdDate' => fake()->dateTimeBetween('-5 years')->format('c'),
                'lastSignInDate' => fake()->dateTimeBetween('-30 days')->format('c'),
            ],

            'primary_email_address_display' => [
                'maskedEmail' => substr($email, 0, 3) . '***@' . explode('@', $email)[1],
                'verificationStatus' => fake()->randomElement(['verified', 'pending', 'unverified']),
                'displayFormat' => fake()->randomElement(['full', 'masked', 'domain_only']),
            ],

            'account' => [
                'accountId' => fake()->uuid(),
                'status' => fake()->randomElement(['active', 'suspended', 'pending']),
                'tier' => fake()->randomElement(['basic', 'premium', 'family']),
                'region' => fake()->countryCode(),
                'language' => fake()->languageCode(),
                'timezone' => fake()->timezone(),
                'subscriptions' => fake()->randomElements([
                    'icloud_storage',
                    'apple_music',
                    'apple_tv',
                    'apple_arcade'
                ], fake()->numberBetween(0, 3)),
            ],

            'edit_alternate_email' => [
                'allowEdit' => fake()->boolean(70),
                'requireReauth' => fake()->boolean(80),
                'cooldownPeriod' => fake()->numberBetween(24, 168), // hours
            ],

            'support_links' => [
                [
                    'title' => 'Account Security',
                    'url' => fake()->url(),
                    'category' => 'security',
                ],
                [
                    'title' => 'Privacy Settings',
                    'url' => fake()->url(),
                    'category' => 'privacy',
                ],
                [
                    'title' => 'Family Sharing',
                    'url' => fake()->url(),
                    'category' => 'family',
                ],
            ],

            'modules' => [
                'security' => [
                    'enabled' => fake()->boolean(95),
                    'features' => fake()->randomElements([
                        'two_factor',
                        'recovery_key',
                        'trusted_devices'
                    ], fake()->numberBetween(1, 3)),
                ],
                'privacy' => [
                    'enabled' => fake()->boolean(90),
                    'settings' => fake()->randomElements([
                        'data_sharing',
                        'analytics',
                        'personalization'
                    ], fake()->numberBetween(1, 3)),
                ],
                'family' => [
                    'enabled' => fake()->boolean(40),
                    'role' => fake()->randomElement(['organizer', 'member', 'child']),
                ],
            ],

            'countries_with_phone_number_removal_restriction' => fake()->randomElements([
                'CN',
                'RU',
                'IR',
                'KP',
                'BY'
            ], fake()->numberBetween(0, 3)),

            'localized_resources' => [
                'language' => fake()->languageCode(),
                'region' => fake()->countryCode(),
                'dateFormat' => fake()->randomElement(['MM/DD/YYYY', 'DD/MM/YYYY', 'YYYY-MM-DD']),
                'timeFormat' => fake()->randomElement(['12h', '24h']),
                'currency' => fake()->currencyCode(),
            ],

            'apple_id_email_merge' => [
                'canMerge' => fake()->boolean(60),
                'pendingMerge' => fake()->boolean(10),
                'eligibleEmails' => fake()->randomElements([
                    fake()->email(),
                    fake()->email(),
                ], fake()->numberBetween(0, 2)),
            ],

            'config' => [
                'theme' => fake()->randomElement(['light', 'dark', 'auto']),
                'notifications' => [
                    'email' => fake()->boolean(80),
                    'push' => fake()->boolean(70),
                    'sms' => fake()->boolean(40),
                ],
                'security' => [
                    'sessionTimeout' => fake()->numberBetween(30, 1440), // minutes
                    'requireReauth' => fake()->boolean(60),
                ],
            ],
        ];
    }

    /**
     * 创建付费账户状态
     *
     * @return static
     */
    public function paidAccount(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_paid_account' => true,
            'is_hsa_eligible' => true,
            'is_hsa' => true,
        ]);
    }

    /**
     * 创建家庭账户组织者状态
     *
     * @return static
     */
    public function familyOrganizer(): static
    {
        return $this->state(fn(array $attributes) => [
            'should_show_beneficiary_ui' => true,
            'should_show_custodian_ui' => true,
            'modules' => array_merge($attributes['modules'] ?? [], [
                'family' => [
                    'enabled' => true,
                    'role' => 'organizer',
                ],
            ]),
        ]);
    }

    /**
     * 创建高安全级别账户状态
     *
     * @return static
     */
    public function highSecurity(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_hsa' => true,
            'show_hsa2_recovery_key_section' => true,
            'should_show_recovery_key_ui' => true,
            'exceeded_verification_attempts' => false,
            'scnt_required' => true,
        ]);
    }

    /**
     * 创建基础账户状态
     *
     * @return static
     */
    public function basicAccount(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_paid_account' => false,
            'is_hsa_eligible' => false,
            'is_hsa' => false,
            'should_show_beneficiary_ui' => false,
            'should_show_custodian_ui' => false,
        ]);
    }

    /**
     * 为指定账户创建账户管理器
     *
     * @param Account $account
     * @return static
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn(array $attributes) => [
            'account_id' => $account->id,
        ]);
    }
}
