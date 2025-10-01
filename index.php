<?php

class PackageCreator
{
    private $basePath;
    private $structure;

    public function __construct($basePath = 'user-auth-package')
    {
        $this->basePath = $basePath;
        $this->structure = [
            'config/user-auth.php' => $this->getConfigContent(),
            'database/migrations/2024_01_01_000001_create_user_types_table.php' => $this->getUserTypesMigrationContent(),
            'database/migrations/2024_01_01_000002_create_users_table.php' => $this->getUsersMigrationContent(),
            'database/migrations/2024_01_01_000003_create_otp_codes_table.php' => $this->getOtpCodesMigrationContent(),
            'database/seeds/UserTypeSeeder.php' => $this->getUserTypeSeederContent(),
            'routes/web.php' => $this->getRoutesContent(),
            'src/Contracts/AuthDriverInterface.php' => $this->getAuthDriverInterfaceContent(),
            'src/Contracts/SsoDriverInterface.php' => $this->getSsoDriverInterfaceContent(),
            'src/Drivers/SamlDriver.php' => $this->getSamlDriverContent(),
            'src/Drivers/OpenIdDriver.php' => $this->getOpenIdDriverContent(),
            'src/Http/Controllers/AuthController.php' => $this->getAuthControllerContent(),
            'src/Models/User.php' => $this->getUserModelContent(),
            'src/Models/UserType.php' => $this->getUserTypeModelContent(),
            'src/Models/OtpCode.php' => $this->getOtpCodeModelContent(),
            'src/Services/AuthService.php' => $this->getAuthServiceContent(),
            'src/UserAuthServiceProvider.php' => $this->getServiceProviderContent(),
            'composer.json' => $this->getComposerContent(),
            'README.md' => $this->getReadmeContent()
        ];
    }

    public function createPackage()
    {
        echo "Creating package structure...\n";

        // Create base directory
        if (!file_exists($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }

        $createdCount = 0;
        $errorCount = 0;

        foreach ($this->structure as $filePath => $content) {
            $fullPath = $this->basePath . '/' . $filePath;
            $dir = dirname($fullPath);

            // Create directory if it doesn't exist
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            // Create file with content
            if (file_put_contents($fullPath, $content) !== false) {
                echo "✓ Created: $filePath\n";
                $createdCount++;
            } else {
                echo "✗ Failed to create: $filePath\n";
                $errorCount++;
            }
        }

        echo "\nPackage creation completed!\n";
        echo "✓ Successfully created: $createdCount files\n";
        if ($errorCount > 0) {
            echo "✗ Errors: $errorCount files\n";
        }
        echo "Package location: " . realpath($this->basePath) . "\n";
    }

    public function createZip($zipFilename = 'user-auth-package.zip')
    {
        $zip = new ZipArchive();

        if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->basePath),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($this->basePath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();
            echo "✓ ZIP package created: $zipFilename\n";
            return true;
        }

        echo "✗ Failed to create ZIP file\n";
        return false;
    }

    private function getConfigContent()
    {
        return '<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Authentication Method
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication method.
    | Supported: "sso", "otp"
    |
    */
    \'default_auth_method\' => env(\'USER_AUTH_METHOD\', \'sso\'),

    /*
    |--------------------------------------------------------------------------
    | SSO Configuration
    |--------------------------------------------------------------------------
    */
    \'sso\' => [
        \'enabled\' => env(\'SSO_ENABLED\', true),
        \'default_protocol\' => env(\'SSO_DEFAULT_PROTOCOL\', \'saml\'),
        
        \'protocols\' => [
            \'saml\' => [
                \'enabled\' => env(\'SAML_ENABLED\', false),
                \'idp_entity_id\' => env(\'SAML_IDP_ENTITY_ID\'),
                \'idp_sso_url\' => env(\'SAML_IDP_SSO_URL\'),
                \'idp_slo_url\' => env(\'SAML_IDP_SLO_URL\'),
                \'idp_x509_cert\' => env(\'SAML_IDP_X509_CERT\'),
                \'sp_entity_id\' => env(\'SAML_SP_ENTITY_ID\'),
            ],
            
            \'openid\' => [
                \'enabled\' => env(\'OPENID_ENABLED\', false),
                \'client_id\' => env(\'OPENID_CLIENT_ID\'),
                \'client_secret\' => env(\'OPENID_CLIENT_SECRET\'),
                \'issuer\' => env(\'OPENID_ISSUER\'),
                \'authorization_endpoint\' => env(\'OPENID_AUTHORIZATION_ENDPOINT\'),
                \'token_endpoint\' => env(\'OPENID_TOKEN_ENDPOINT\'),
                \'userinfo_endpoint\' => env(\'OPENID_USERINFO_ENDPOINT\'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    */
    \'otp\' => [
        \'enabled\' => env(\'OTP_ENABLED\', true),
        \'length\' => env(\'OTP_LENGTH\', 6),
        \'expiry\' => env(\'OTP_EXPIRY\', 10), // minutes
        \'throttle\' => env(\'OTP_THROTTLE\', 1), // minutes between requests
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    */
    \'development\' => [
        \'test_email\' => env(\'TEST_EMAIL\', \'test@example.com\'),
        \'bypass_sso\' => env(\'BYPASS_SSO\', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Types
    |--------------------------------------------------------------------------
    */
    \'user_types\' => [
        \'admin\' => [
            \'label\' => \'Administrator\',
            \'permissions\' => [\'*\'],
        ],
        \'user\' => [
            \'label\' => \'Regular User\',
            \'permissions\' => [\'read\', \'write\'],
        ],
        \'guest\' => [
            \'label\' => \'Guest\',
            \'permissions\' => [\'read\'],
        ],
    ],
];
';
    }

    private function getUserTypesMigrationContent()
    {
        return '<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create(\'user_types\', function (Blueprint $table) {
            $table->id();
            $table->string(\'name\')->unique();
            $table->string(\'label\');
            $table->json(\'permissions\')->nullable();
            $table->boolean(\'is_active\')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists(\'user_types\');
    }
};
';
    }

    private function getUsersMigrationContent()
    {
        return '<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create(\'users\', function (Blueprint $table) {
            $table->id();
            $table->string(\'name\');
            $table->string(\'email\')->unique();
            $table->foreignId(\'user_type_id\')->constrained()->onDelete(\'cascade\');
            $table->string(\'sso_id\')->nullable()->unique();
            $table->string(\'sso_provider\')->nullable();
            $table->json(\'sso_attributes\')->nullable();
            $table->boolean(\'is_active\')->default(true);
            $table->timestamp(\'email_verified_at\')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists(\'users\');
    }
};
';
    }

    private function getOtpCodesMigrationContent()
    {
        return '<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create(\'otp_codes\', function (Blueprint $table) {
            $table->id();
            $table->string(\'email\');
            $table->string(\'code\');
            $table->string(\'token\');
            $table->timestamp(\'expires_at\');
            $table->boolean(\'used\')->default(false);
            $table->timestamps();
            
            $table->index([\'email\', \'used\']);
            $table->index([\'token\']);
        });
    }

    public function down()
    {
        Schema::dropIfExists(\'otp_codes\');
    }
};
';
    }

    private function getUserTypeSeederContent()
    {
        return '<?php

namespace MostafaFathi\UserAuth\Database\Seeds;

use Illuminate\Database\Seeder;
use MostafaFathi\UserAuth\Models\UserType;

class UserTypeSeeder extends Seeder
{
    public function run()
    {
        $userTypes = config(\'user-auth.user_types\', []);
        
        foreach ($userTypes as $name => $config) {
            UserType::updateOrCreate(
                [\'name\' => $name],
                [
                    \'label\' => $config[\'label\'],
                    \'permissions\' => $config[\'permissions\'],
                    \'is_active\' => true,
                ]
            );
        }
    }
}
';
    }

    private function getRoutesContent()
    {
        return '<?php

use Illuminate\Support\Facades\Route;
use MostafaFathi\UserAuth\Http\Controllers\AuthController;

Route::middleware(\'web\')->group(function () {
    // SSO Routes
    Route::get(\'/auth/sso/redirect\', [AuthController::class, \'redirectToSso\'])->name(\'sso.redirect\');
    Route::get(\'/auth/sso/callback\', [AuthController::class, \'ssoCallback\'])->name(\'sso.callback\');
    
    // OTP Routes
    Route::post(\'/auth/otp/request\', [AuthController::class, \'requestOtp\'])->name(\'otp.request\');
    Route::post(\'/auth/otp/verify\', [AuthController::class, \'verifyOtp\'])->name(\'otp.verify\');
    
    // Logout
    Route::post(\'/auth/logout\', [AuthController::class, \'logout\'])->name(\'logout\');
});
';
    }

    private function getAuthDriverInterfaceContent()
    {
        return '<?php

namespace MostafaFathi\UserAuth\Contracts;

interface AuthDriverInterface
{
    public function authenticate(array $credentials): bool;
    
    public function getUser(): ?object;
    
    public function logout(): void;
}
';
    }

    private function getSsoDriverInterfaceContent()
    {
        return '<?php

namespace MostafaFathi\UserAuth\Contracts;

interface SsoDriverInterface
{
    public function redirect();
    
    public function user(): ?array;
    
    public function logout();
    
    public function getEmail(): ?string;
    
    public function getName(): ?string;
    
    public function getAttributes(): array;
}
';
    }

    private function getSamlDriverContent()
    {
        return '<?php

namespace MostafaFathi\UserAuth\Drivers;

use MostafaFathi\UserAuth\Contracts\SsoDriverInterface;

class SamlDriver implements SsoDriverInterface
{
    protected $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    public function redirect()
    {
        // Implement SAML redirect logic
        // You can use packages like lightsaml/lightsaml
    }
    
    public function user(): ?array
    {
        // Process SAML response and return user data
        return [
            \'id\' => \'saml_user_id\',
            \'email\' => \'user@example.com\',
            \'name\' => \'John Doe\',
            \'attributes\' => [],
        ];
    }
    
    public function logout()
    {
        // Implement SAML logout
    }
    
    public function getEmail(): ?string
    {
        $user = $this->user();
        return $user[\'email\'] ?? null;
    }
    
    public function getName(): ?string
    {
        $user = $this->user();
        return $user[\'name\'] ?? null;
    }
    
    public function getAttributes(): array
    {
        $user = $this->user();
        return $user[\'attributes\'] ?? [];
    }
}
';
    }

    private function getOpenIdDriverContent()
    {
        return '<?php

namespace MostafaFathi\UserAuth\Drivers;

use MostafaFathi\UserAuth\Contracts\SsoDriverInterface;

class OpenIdDriver implements SsoDriverInterface
{
    protected $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    public function redirect()
    {
        // Implement OpenID Connect redirect
        // You can use packages like thenetworg/oauth2-azure
    }
    
    public function user(): ?array
    {
        // Process OpenID Connect response
        return [
            \'id\' => \'openid_user_id\',
            \'email\' => \'user@example.com\',
            \'name\' => \'John Doe\',
            \'attributes\' => [],
        ];
    }
    
    public function logout()
    {
        // Implement OpenID Connect logout
    }
    
    public function getEmail(): ?string
    {
        $user = $this->user();
        return $user[\'email\'] ?? null;
    }
    
    public function getName(): ?string
    {
        $user = $this->user();
        return $user[\'name\'] ?? null;
    }
    
    public function getAttributes(): array
    {
        $user = $this->user();
        return $user[\'attributes\'] ?? [];
    }
}
';
    }

    private function getAuthControllerContent()
    {
        return '<?php

namespace MostafaFathi\UserAuth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use MostafaFathi\UserAuth\Services\AuthService;

class AuthController extends Controller
{
    protected $authService;
    
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    
    public function redirectToSso(Request $request)
    {
        $protocol = $request->get(\'protocol\', config(\'user-auth.sso.default_protocol\'));
        
        // Implement protocol-based redirection
        // This would use the appropriate driver
    }
    
    public function ssoCallback(Request $request)
    {
        // Check if we should use test user in development
        if (config(\'user-auth.development.bypass_sso\') && $testUser = $this->authService->getTestUser()) {
            Auth::login($testUser);
            return redirect()->intended(\'/\');
        }
        
        $user = $this->authService->handleSsoAuthentication();
        
        if ($user) {
            Auth::login($user);
            return redirect()->intended(\'/\');
        }
        
        return redirect()->route(\'login\')->withErrors([
            \'sso\' => \'SSO authentication failed.\',
        ]);
    }
    
    public function requestOtp(Request $request)
    {
        $request->validate([
            \'email\' => \'required|email\',
        ]);
        
        $token = $this->authService->sendOtp($request->email);
        
        if (!$token) {
            return response()->json([
                \'error\' => \'Please wait before requesting another OTP.\',
            ], 429);
        }
        
        return response()->json([
            \'message\' => \'OTP sent successfully.\',
            \'token\' => $token,
        ]);
    }
    
    public function verifyOtp(Request $request)
    {
        $request->validate([
            \'token\' => \'required\',
            \'code\' => \'required\',
        ]);
        
        $user = $this->authService->verifyOtp($request->token, $request->code);
        
        if ($user) {
            Auth::login($user);
            
            return response()->json([
                \'message\' => \'Login successful.\',
                \'user\' => $user,
            ]);
        }
        
        return response()->json([
            \'error\' => \'Invalid OTP code.\',
        ], 401);
    }
    
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect(\'/\');
    }
}
';
    }

    private function getUserModelContent()
    {
        return '<?php

namespace MostafaFathi\UserAuth\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        \'name\',
        \'email\',
        \'user_type_id\',
        \'sso_id\',
        \'sso_provider\',
        \'sso_attributes\',
        \'is_active\',
        \'email_verified_at\',
    ];

    protected $hidden = [
        \'remember_token\',
    ];

    protected $casts = [
        \'sso_attributes\' => \'array\',
        \'is_active\' => \'boolean\',
        \'email_verified_at\' => \'datetime\',
    ];

    public function userType()
    {
        return $this->belongsTo(UserType::class);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->userType->hasPermission($permission);
    }

    public function isAdmin(): bool
    {
        return $this->userType->name === \'admin\';
    }
}
';
    }

    private function getUserTypeModelContent()
    {
        return '<?php

namespace MostafaFathi\UserAuth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserType extends Model
{
    protected $fillable = [
        \'name\',
        \'label\',
        \'permissions\',
        \'is_active\',
    ];

    protected $casts = [
        \'permissions\' => \'array\',
        \'is_active\' => \'boolean\',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        if (in_array(\'*\', $this->permissions ?? [])) {
            return true;
        }

        return in_array($permission, $this->permissions ?? []);
    }
}
';
    }

    private function getOtpCodeModelContent()
    {
        return '<?php

namespace MostafaFathi\UserAuth\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        \'email\',
        \'code\',
        \'token\',
        \'expires_at\',
        \'used\',
    ];

    protected $casts = [
        \'expires_at\' => \'datetime\',
        \'used\' => \'boolean\',
    ];

    public function isValid(): bool
    {
        return !$this->used && $this->expires_at->isFuture();
    }

    public function markAsUsed(): void
    {
        $this->update([\'used\' => true]);
    }
}
';
    }

    private function getAuthServiceContent()
    {
        return '<?php

namespace MostafaFathi\UserAuth\Services;

use MostafaFathi\UserAuth\Models\User;
use MostafaFathi\UserAuth\Models\UserType;
use MostafaFathi\UserAuth\Models\OtpCode;
use MostafaFathi\UserAuth\Contracts\SsoDriverInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthService
{
    protected $ssoDriver;
    
    public function setSsoDriver(SsoDriverInterface $driver): self
    {
        $this->ssoDriver = $driver;
        return $this;
    }
    
    public function handleSsoAuthentication(): ?User
    {
        if (!$this->ssoDriver) {
            return null;
        }
        
        $ssoUser = $this->ssoDriver->user();
        
        if (!$ssoUser || !$email = $this->ssoDriver->getEmail()) {
            return null;
        }
        
        // Check if user exists by SSO ID or email
        $user = User::where(\'sso_id\', $ssoUser[\'id\'])
            ->orWhere(\'email\', $email)
            ->first();
            
        if (!$user) {
            $user = $this->createUserFromSso($ssoUser);
        } else {
            $user = $this->updateUserFromSso($user, $ssoUser);
        }
        
        return $user;
    }
    
    protected function createUserFromSso(array $ssoUser): User
    {
        $defaultUserType = UserType::where(\'name\', \'user\')->first();
        
        return User::create([
            \'name\' => $this->ssoDriver->getName() ?? $ssoUser[\'email\'],
            \'email\' => $this->ssoDriver->getEmail(),
            \'user_type_id\' => $defaultUserType->id,
            \'sso_id\' => $ssoUser[\'id\'],
            \'sso_provider\' => config(\'user-auth.sso.default_protocol\'),
            \'sso_attributes\' => $this->ssoDriver->getAttributes(),
            \'email_verified_at\' => now(),
        ]);
    }
    
    protected function updateUserFromSso(User $user, array $ssoUser): User
    {
        $user->update([
            \'sso_id\' => $ssoUser[\'id\'],
            \'sso_provider\' => config(\'user-auth.sso.default_protocol\'),
            \'sso_attributes\' => $this->ssoDriver->getAttributes(),
        ]);
        
        return $user;
    }
    
    public function sendOtp(string $email): ?string
    {
        // Check throttle
        $recentOtp = OtpCode::where(\'email\', $email)
            ->where(\'created_at\', \'>\', now()->subMinutes(config(\'user-auth.otp.throttle\')))
            ->first();
            
        if ($recentOtp) {
            return null;
        }
        
        $code = $this->generateOtpCode();
        $token = Str::random(60);
        
        OtpCode::create([
            \'email\' => $email,
            \'code\' => $code,
            \'token\' => $token,
            \'expires_at\' => now()->addMinutes(config(\'user-auth.otp.expiry\')),
        ]);
        
        // Here you would send the email with OTP code
        // Mail::to($email)->send(new OtpMail($code));
        
        return $token;
    }
    
    public function verifyOtp(string $token, string $code): ?User
    {
        $otp = OtpCode::where(\'token\', $token)
            ->where(\'code\', $code)
            ->first();
            
        if (!$otp || !$otp->isValid()) {
            return null;
        }
        
        $otp->markAsUsed();
        
        $user = User::where(\'email\', $otp->email)->first();
        
        if (!$user) {
            $defaultUserType = UserType::where(\'name\', \'user\')->first();
            
            $user = User::create([
                \'name\' => explode(\'@\', $otp->email)[0],
                \'email\' => $otp->email,
                \'user_type_id\' => $defaultUserType->id,
                \'email_verified_at\' => now(),
            ]);
        }
        
        return $user;
    }
    
    protected function generateOtpCode(): string
    {
        $length = config(\'user-auth.otp.length\', 6);
        
        return str_pad(mt_rand(0, pow(10, $length) - 1), $length, \'0\', STR_PAD_LEFT);
    }
    
    public function getTestUser(): ?User
    {
        $testEmail = config(\'user-auth.development.test_email\');
        
        if (!$testEmail || $testEmail === \'test@example.com\') {
            return null;
        }
        
        return User::where(\'email\', $testEmail)->first();
    }
}
';
    }

    private function getServiceProviderContent()
    {
        return '<?php

namespace MostafaFathi\UserAuth;

use Illuminate\Support\ServiceProvider;
use MostafaFathi\UserAuth\Services\AuthService;

class UserAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.\'/../../config/user-auth.php\' => config_path(\'user-auth.php\'),
        ], \'user-auth-config\');
        
        $this->publishes([
            __DIR__.\'/../../database/migrations\' => database_path(\'migrations\'),
        ], \'user-auth-migrations\');
        
        $this->loadRoutesFrom(__DIR__.\'/../../routes/web.php\');
        $this->loadViewsFrom(__DIR__.\'/../../resources/views\', \'user-auth\');
    }
    
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.\'/../../config/user-auth.php\', \'user-auth\'
        );
        
        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService();
        });
    }
}
';
    }

    private function getComposerContent()
    {
        return '{
    "name": "MostafaFathi/user-auth",
    "description": "Laravel package for user type management with SSO and OTP authentication",
    "type": "library",
    "require": {
        "php": "^8.0",
        "laravel/framework": "^9.0|^10.0"
    },
    "autoload": {
        "psr-4": {
            "MostafaFathi\\\\UserAuth\\\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "MostafaFathi\\\\UserAuth\\\\UserAuthServiceProvider"
            ]
        }
    }
}';
    }

    private function getReadmeContent()
    {
        return '';
    }
}
$new = new PackageCreator();
$new->createPackage();