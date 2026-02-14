<?php

use App\Models\User;
use App\Models\Option;
use App\Models\Utility\Business;
use App\Models\Currency;
use App\Models\UserCurrency;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Cache;
use App\Notifications\SendNotification;
use Illuminate\Support\Facades\Notification;

function cache_remember(string $key, callable $callback, int $ttl = 1800): mixed
{
    return cache()->remember($key, env('CACHE_LIFETIME', $ttl), $callback);
}

function invoice_setting()
{
    return get_option('invoice_setting_' . auth()->user()->business_id);
}

function get_option($key)
{
    return cache_remember($key, function () use ($key) {
        return Option::where('key', $key)->first()->value ?? [];
    });
}



function formatted_date(?string $date = null, string $format = 'd M, Y'): ?string
{
    return !empty($date) ? Date::parse($date)->format($format) : null;
}

function formatted_time(?string $time = null, string $format = 'h:i A'): ?string
{
    return !empty($time) ? Date::parse($time)->format($format) : null;
}

function sendNotification($id, $url, $message, $user = null)
{
    $notify = [
        'id' => $id,
        'url' => $url,
        'user' => $user,
        'message' => $message,
    ];

    $notify_user = User::where('role', 'super admin')->first();
    Notification::send($notify_user, new SendNotification($notify));
}

function sendNotifyToUser($id, $url, $message, $user)
{
    $notify = [
        'id' => $id,
        'url' => $url,
        'user' => $user,
        'message' => $message,
    ];

    $notify_user = User::where('business_id', $user)->first();
    Notification::send($notify_user, new SendNotification($notify));
}

function currency_format($amount, $type = "icon", $decimals = 2, $currency = null)
{
    $amount = number_format($amount, $decimals);
    $currency = $currency ?? default_currency();

    if ($type == "icon" || $type == "symbol") {
        if ($currency->position == "right") {
            return $amount . $currency->symbol;
        } else {
            return $currency->symbol . $amount;
        }
    } else {
        if ($currency->position == "right") {
            return $amount . ' ' . $currency->code;
        } else {
            return $currency->code . ' ' . $amount;
        }
    }
}

function default_currency($key = null, ?Currency $currency = null): object|int|string
{
    $currency = $currency ?? cache_remember('default_currency', function () {
        $currency = Currency::whereIsDefault(1)->first();

        if (!$currency) {
            $currency = (object) ['name' => 'US Dollar', 'code' => 'USD', 'rate' => 1, 'symbol' => '$', 'position' => 'left', 'status' => true, 'is_default' => true,];
        }

        return $currency;
    });

    return $key ? $currency->$key : $currency;
}


function convert_money($amount, $currency, $multiply = true)
{
    if ($currency->code == default_currency('code')) {
        return $amount;
    } else {
        if ($multiply) {
            return $amount * $currency->rate;
        } else {
            return $amount / $currency->rate;
        }
    }
}

// Format phone number
if (!function_exists('formatPhoneNumber')) {
    function formatPhoneNumber($phone)
    {
        return substr($phone, 0, 2) . '*****' . substr($phone, -2);
    }
}

if (!function_exists('formatUserName')) {
    function formatUserName($name)
    {
        $parts = explode(' ', $name);
        $initials = array_map(function ($part) {
            return strtoupper($part[0]);
        }, $parts);

        return implode('', $initials);
    }
}




function business_currency($business_id = null)
{
    $business_id = $business_id ?? auth()->user()->business_id;

    return cache_remember("business_currency_{$business_id}", function () use ($business_id) {
        $businessCurrency = UserCurrency::where('company_id', $business_id)->first() ?? Currency::where('is_default', 1)->first();
        ;

        if ($businessCurrency) {
            return (object) [
                'name' => $businessCurrency->name,
                'rate' => $businessCurrency->rate,
                'code' => $businessCurrency->code,
                'symbol' => $businessCurrency->symbol,
                'position' => $businessCurrency->position,
            ];
        }

        return default_currency();
    });
}

function get_business_option($key)
{
    $cacheKey = "business_setting_" . auth()->user()->business_id;

    return Cache::remember($cacheKey, now()->addDay(), function () use ($key) {
        if ($key === 'business-settings') {
            return Option::where('key', 'business-settings')
                ->whereJsonContains('value->business_id', auth()->user()->business_id)
                ->first()
                ->value ?? null;
        }
        return null;
    });
}

// user role permission
if (!function_exists('visible_permission')) {
    function visible_permission($permission)
    {
        $user = auth()->user();

        // Ensure the user is authenticated and has a business_id
        if (!$user || !$user->business_id) {
            return false;
        }

        // Handle visibility field directly as an array or decode it if it's a string
        $permissions = is_array($user->visibility)
            ? $user->visibility
            : json_decode($user->visibility, true);

        return $permissions[$permission] ?? false;
    }
}




function languages()
{
    return [
        'en' => ['name' => 'English', 'flag' => 'us'],
        'ar' => ['name' => 'Arabic', 'flag' => 'sa'],
        'bn' => ['name' => 'Bengali', 'flag' => 'bd'],
        'zh' => ['name' => 'Chinese', 'flag' => 'cn'],
        'fr' => ['name' => 'French', 'flag' => 'fr'],
        'de' => ['name' => 'German', 'flag' => 'de'],
        'hi' => ['name' => 'Hindi', 'flag' => 'in'],
        'es' => ['name' => 'Spanish', 'flag' => 'es'],
        'ja' => ['name' => 'Japanese', 'flag' => 'jp'],
        'rum' => ['name' => 'Romanian', 'flag' => 'ro'],
        'vi' => ['name' => 'Vietnamese', 'flag' => 'vn'],
        'it' => ['name' => 'Italian', 'flag' => 'it'],
        'th' => ['name' => 'Thai', 'flag' => 'th'],
        'bs' => ['name' => 'Bosnian', 'flag' => 'ba'],
        'nl' => ['name' => 'Dutch', 'flag' => 'nl'],
        'pt' => ['name' => 'Portuguese', 'flag' => 'pt'],
        'pl' => ['name' => 'Polish', 'flag' => 'pl'],
        'he' => ['name' => 'Hebrew', 'flag' => 'il'],
        'hu' => ['name' => 'Hungarian', 'flag' => 'hu'],
        'fi' => ['name' => 'Finnish', 'flag' => 'fi'],
        'el' => ['name' => 'Greek', 'flag' => 'gr'],
        'ko' => ['name' => 'Korean', 'flag' => 'kr'],
        'ms' => ['name' => 'Malay', 'flag' => 'my'],
        'id' => ['name' => 'Indonesian', 'flag' => 'id'],
        'fa' => ['name' => 'Persian', 'flag' => 'ir'],
        'tr' => ['name' => 'Turkish', 'flag' => 'tr'],
        'sr' => ['name' => 'Serbian', 'flag' => 'rs'],
        'km' => ['name' => 'Khmer', 'flag' => 'khm'],
        'uk' => ['name' => 'Ukrainian', 'flag' => 'ua'],
        'lo' => ['name' => 'Lao', 'flag' => 'la'],
        'ru' => ['name' => 'Russian', 'flag' => 'ru'],
        'cs' => ['name' => 'Czech', 'flag' => 'cz'],
        'kn' => ['name' => 'Kannada', 'flag' => 'ka'],
        'mr' => ['name' => 'Marathi', 'flag' => 'mh'],
        'sv' => ['name' => 'Swedish', 'flag' => 'se'],
        'da' => ['name' => 'Danish', 'flag' => 'dk'],
        'ur' => ['name' => 'Urdu', 'flag' => 'pk'],
        'sq' => ['name' => 'Albanian', 'flag' => 'al'],
        'sk' => ['name' => 'Slovak', 'flag' => 'sk'],
        'bur' => ['name' => 'Burmese', 'flag' => 'mm'],
        'ti' => ['name' => 'Tigrinya', 'flag' => 'er'],
        'kz' => ['name' => 'Kazakh', 'flag' => 'kz'],
        'az' => ['name' => 'Azerbaijani', 'flag' => 'az'],
        'zh-cn' => ['name' => 'Chinese (CN)', 'flag' => 'zh-cn'],
        'zh-tw' => ['name' => 'Chinese (TW)', 'flag' => 'zh-tw'],
        'pt-br' => ['name' => 'Portuguese (BR)', 'flag' => 'pt-br'],
        'tz' => ['name' => 'Swahili', 'flag' => 'tz'],
        'ps' => ['name' => 'Pashto', 'flag' => 'af'],
        'prs' => ['name' => 'Dari', 'flag' => 'afdari'],
        'ca' => ['name' => 'Catalan', 'flag' => 'ad'],
        'bt' => ['name' => 'Dzongkha', 'flag' => 'dz'],
        'drcfr' => ['name' => 'Congo (DRC)', 'flag' => 'drc'],
        'cgfr' => ['name' => 'Congo (Republic)', 'flag' => 'cg'],
        'escr' => ['name' => 'Costa Rica (Spanish)', 'flag' => 'cr'],
        'enbw' => ['name' => 'Botswana (English)', 'flag' => 'bw'],
        'bws' => ['name' => 'Botswana (Setswana)', 'flag' => 'bws'],
        'deat' => ['name' => 'Austria(German)', 'flag' => 'at'],
        'enbs' => ['name' => 'Bahamas(English)', 'flag' => 'bs'],
        'arbh' => ['name' => 'Bahrain(Arabic)', 'flag' => 'bh'],
        'pt-ao' => ['name' => 'Angola(Portuguese)', 'flag' => 'ao'],
        'es-ar' => ['name' => 'Argentina(Spanish)', 'flag' => 'ar'],
        'hy' => ['name' => 'Armenian', 'flag' => 'am'],
        'au-en' => ['name' => 'Australia', 'flag' => 'au'],
        'bb-en' => ['name' => 'Barbados(English)', 'flag' => 'bb'],
        'be' => ['name' => 'Belarusian', 'flag' => 'by'],
        'nl-be' => ['name' => 'Belgium(Dutch)', 'flag' => 'be'],
        'bz-en' => ['name' => 'Belize(English)', 'flag' => 'bz'],
        'bj-fr' => ['name' => 'Benin(French)', 'flag' => 'bj'],
        'bo-es' => ['name' => 'Bolivia(Spanish)', 'flag' => 'bo'],
        'bn-ms' => ['name' => 'Brunei(Malay)', 'flag' => 'bn'],
        'bg' => ['name' => 'Bulgarian', 'flag' => 'bg'],
        'bf-fr' => ['name' => 'Burkina Faso(French)', 'flag' => 'bf'],
        'cm-fr' => ['name' => 'Cameroon(French)', 'flag' => 'cm'],
        'ca-en' => ['name' => 'Canada(English)', 'flag' => 'ca'],
        'cl-es' => ['name' => 'Chile(Spanish)', 'flag' => 'cl'],
        'co-es' => ['name' => 'Colombia(Spanish)', 'flag' => 'co'],
        'km-ar' => ['name' => 'Comoros(Arabic)', 'flag' => 'km'],
        'hr' => ['name' => 'Croatian', 'flag' => 'hr'],
        'cu-es' => ['name' => 'Cuba(Spanish)', 'flag' => 'cu'],
        'cy-el' => ['name' => 'Cyprus(Greek)', 'flag' => 'cy'],
        'dj-fr' => ['name' => 'Djibouti(French)', 'flag' => 'dj'],
        'dm-en' => ['name' => 'Dominica(English)', 'flag' => 'dm'],
        'tet' => ['name' => 'Tetum', 'flag' => 'tl'],
        'ec-es' => ['name' => 'Ecuador(Spanish)', 'flag' => 'ec'],
        'eg-ar' => ['name' => 'Egypt(Arabic)', 'flag' => 'eg'],
        'sv-es' => ['name' => 'El Salvador(Spanish)', 'flag' => 'sv'],
        'gq-es' => ['name' => 'Equatorial Guinea(Spanish)', 'flag' => 'gq'],
        'et' => ['name' => 'Estonian', 'flag' => 'ee'],
        'ss' => ['name' => 'Swati', 'flag' => 'sz'],
        'am' => ['name' => 'Amharic', 'flag' => 'et'],
        'fj' => ['name' => 'Fijian', 'flag' => 'fj'],
        'ga-fr' => ['name' => 'Gabon(French)', 'flag' => 'ga'],
        'gm-en' => ['name' => 'Gambia(English)', 'flag' => 'gm'],
        'ka' => ['name' => 'Georgian', 'flag' => 'ge'],
        'gh-en' => ['name' => 'Ghana(English)', 'flag' => 'gh'],
        'gd-en' => ['name' => 'Grenada(English)', 'flag' => 'gd'],
        'gt-en' => ['name' => 'Guatemala(English)', 'flag' => 'gt'],
        'gn-fr' => ['name' => 'Guinea(French)', 'flag' => 'gn'],
        'gy-en' => ['name' => 'Guyana(English)', 'flag' => 'gy'],
        'ht-fr' => ['name' => 'Haiti(French)', 'flag' => 'ht'],
        'hn-es' => ['name' => 'Honduras(Spanish)', 'flag' => 'hn'],
    ];
}

function getArrow(int|float $last, int|float $current): string
{
    if ($current > $last) {
        return 'up';
    }

    if ($current < $last) {
        return 'down';
    }

    return 'same';
}
