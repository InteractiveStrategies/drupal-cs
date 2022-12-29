<?php declare(strict_types = 1);

namespace ISDrupal\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use SlevomatCodingStandard\Helpers\ReferencedNameHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\UseStatement;
use SlevomatCodingStandard\Helpers\UseStatementHelper;
use function array_flip;
use function array_key_exists;
use function strtolower;
use const T_OPEN_TAG;

class CompilerFriendlyGlobalFunctionsSniff implements Sniff {

  /**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return [
			T_OPEN_TAG,
		];
	}

  public function process(File $phpcsFile, $openTagPointer): void {
    if (TokenHelper::findPrevious($phpcsFile, T_OPEN_TAG, $openTagPointer - 1) !== null) {
      return;
    }

    $tokens = $phpcsFile->getTokens();

    $namespacePointers = NamespaceHelper::getAllNamespacesPointers($phpcsFile);
    $referencedNames = ReferencedNameHelper::getAllReferencedNames($phpcsFile, $openTagPointer);
    $compiler_optimized_functions = array_flip(FunctionHelper::SPECIAL_FUNCTIONS);

    foreach ($referencedNames as $referencedName) {
      $name = $referencedName->getNameAsReferencedInFile();
      $namePointer = $referencedName->getStartPointer();

      if (!$referencedName->isFunction()) {
        continue;
      }

      if (NamespaceHelper::isFullyQualifiedName($name)) {
        continue;
      }

      if (NamespaceHelper::hasNamespace($name)) {
        continue;
      }

      if ($namespacePointers === []) {
        continue;
      }

      $canonicalName = strtolower($name);

      if (!array_key_exists($canonicalName, $compiler_optimized_functions)) {
        continue;
      }

      $useStatements = UseStatementHelper::getUseStatementsForPointer($phpcsFile, $namePointer);

      if (array_key_exists(UseStatement::getUniqueId($referencedName->getType(), $canonicalName), $useStatements)) {
        continue;
      }

      $phpcsFile->addWarning(
        'Native function %s() can be optimized by the PHP compiler if it is clear it is a global function. Since this file is namespaced, allow compiler optimization by either adding "use function %s;" near the top of this file, or by making the function fully qualified "\%s()".',
        $namePointer,
        'CompilerCannotOptimize',
        array_fill(0, 3, $tokens[$namePointer]['content'])
      );
    }
  }

}
