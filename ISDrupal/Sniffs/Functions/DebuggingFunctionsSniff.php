<?php declare(strict_types = 1);

namespace ISDrupal\Sniffs\Functions;

use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\ForbiddenFunctionsSniff;

/**
 * Discourage the use of debugging functions.
 *
 * They're fine locally, we just don't want them committed.
 *
 * Drupal.Functions.DiscouragedFunctions already discourages the use of most
 * Drupal-specific debugging functions, so this focuses on vanilla and Symfony
 * calls.
 */
class DebuggingFunctionsSniff extends ForbiddenFunctionsSniff {

    /**
     * A list of forbidden functions with their alternatives.
     *
     * The value is NULL if no alternative exists, i.e., the function should
     * just not be used.
     *
     * @var array<string, null>
     */
    public $forbiddenFunctions = [
        // PHP debugging functions.
        'debug_backtrace'   => null,
        'error_log'         => null,
        'phpinfo'           => null,
        'print_r'           => null,
        'var_dump'          => null,
        'var_export'        => null,

        // Symfony var dumper functions.
        // 'dd' is omitted because it is already forbidden by Drupal.Functions.DiscouragedFunctions.Discouraged.
        'dump'              => null,

        // Devel module debugging functions.
        'dargs'             => null,
        'ddebug_backtrace'  => null,
        'ddm'               => null,
        'devel_debug'       => null,
        'devel_dump'        => null,
        'devel_export'      => null,
        'devel_message'     => null,
        'devel_render'      => null,
        'devel_set_message' => null,
        'dfb'               => null,
        'dpbt'              => null,
        'dpm'               => null,
        'dpq'               => null,
        'dpr'               => null,
        'dsm'               => null,
        'dvm'               => null,
        'dvr'               => null,

        // Kint module default debugging functions.
        'd'                 => null,
        's'                 => null,
    ];

    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var boolean
     */
    public $error = TRUE;

}
