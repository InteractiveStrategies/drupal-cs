<?php declare(strict_types = 1);

namespace ISDrupal\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\ForbiddenFunctionsSniff;
use PHP_CodeSniffer\Util\Tokens;

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

    /**
     * Functions whose error is relaxed when they return data instead of printing.
     *
     * Both print_r() and var_export() accept an optional second argument that,
     * when truthy, makes the function return its output as a string rather than
     * printing it. That can be a legitimate, committable use (e.g. building a
     * string for a log or trigger_error()), so we downgrade those calls to a
     * warning instead of forbidding them outright.
     *
     * @var string[]
     */
    protected $relaxableReturnFunctions = [
        'print_r',
        'var_export',
    ];

    /**
     * Generates the error or warning for this sniff.
     *
     * Overridden so that print_r() and var_export() calls that pass a truthy
     * "return" argument are reported as a warning rather than an error. The
     * parent only calls this method for confirmed forbidden function calls, so
     * method calls such as $this->print_r() never reach here.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
     * @param int                         $stackPtr     The position of the forbidden function.
     * @param string                      $functionName The name of the forbidden function.
     * @param string|null                 $pattern      The pattern used for the match.
     *
     * @return void
     */
    protected function addError(File $phpcsFile, int $stackPtr, string $functionName, ?string $pattern = NULL) {
        if (in_array($functionName, $this->relaxableReturnFunctions, TRUE) === TRUE
            && $this->hasTruthyReturnArgument($phpcsFile, $stackPtr) === TRUE
        ) {
            $phpcsFile->addWarning(
                'The use of debugging function %s() is discouraged; allowed here because the return argument is set',
                $stackPtr,
                'ReturnArgument',
                [$functionName]
            );
            return;
        }

        parent::addError($phpcsFile, $stackPtr, $functionName, $pattern);
    }

    /**
     * Determines whether a function call's "return" argument is a truthy literal.
     *
     * Only clear literals are treated as truthy (TRUE or a non-zero integer),
     * supplied either positionally as the second argument or as a named
     * "return:" argument. Variables and expressions are deliberately not
     * resolved, so anything we cannot statically confirm keeps the error.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the function name token.
     *
     * @return boolean
     */
    protected function hasTruthyReturnArgument(File $phpcsFile, int $stackPtr) {
        $tokens = $phpcsFile->getTokens();

        $openParen = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), NULL, TRUE);
        if ($openParen === FALSE || $tokens[$openParen]['code'] !== T_OPEN_PARENTHESIS) {
            return FALSE;
        }
        $closeParen = $tokens[$openParen]['parenthesis_closer'];

        // Find the top-level comma separating the first argument from the second,
        // skipping over any nested parentheses, arrays or square brackets.
        $commaPtr = NULL;
        for ($i = ($openParen + 1); $i < $closeParen; $i++) {
            $code = $tokens[$i]['code'];
            if ($code === T_OPEN_PARENTHESIS) {
                $i = $tokens[$i]['parenthesis_closer'];
                continue;
            }
            if (($code === T_OPEN_SHORT_ARRAY || $code === T_OPEN_SQUARE_BRACKET)
                && isset($tokens[$i]['bracket_closer']) === TRUE
            ) {
                $i = $tokens[$i]['bracket_closer'];
                continue;
            }
            if ($code === T_COMMA) {
                $commaPtr = $i;
                break;
            }
        }

        if ($commaPtr === NULL) {
            // Only one argument was passed: not return mode.
            return FALSE;
        }

        // Locate the start of the second argument.
        $valueStart = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($commaPtr + 1), $closeParen, TRUE);
        if ($valueStart === FALSE) {
            return FALSE;
        }

        // Skip a named-argument label, e.g. "return:".
        $afterLabel = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($valueStart + 1), $closeParen, TRUE);
        if ($afterLabel !== FALSE && $tokens[$afterLabel]['code'] === T_COLON) {
            $valueStart = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($afterLabel + 1), $closeParen, TRUE);
            if ($valueStart === FALSE) {
                return FALSE;
            }
        }

        // The argument must be a single literal token (followed by the closing
        // parenthesis or a further argument), otherwise we leave it as an error.
        $valueEnd = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($valueStart + 1), ($closeParen + 1), TRUE);
        if ($valueEnd !== FALSE && $valueEnd < $closeParen && $tokens[$valueEnd]['code'] !== T_COMMA) {
            return FALSE;
        }

        $code    = $tokens[$valueStart]['code'];
        $content = strtolower($tokens[$valueStart]['content']);

        // PHPCS may tokenize TRUE as T_TRUE or, depending on context, T_STRING.
        if ($code === T_TRUE || ($code === T_STRING && $content === 'true')) {
            return TRUE;
        }

        // A non-zero integer literal is also truthy.
        if ($code === T_LNUMBER && (int) $content !== 0) {
            return TRUE;
        }

        return FALSE;
    }

}
