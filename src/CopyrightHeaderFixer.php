<?php

namespace WvnderlabAgency\CopyrightHeader;

use PhpCsFixer\FixerDefinition\FixerDefinition;
use SplFileInfo;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class CopyrightHeaderFixer extends AbstractFixer
{
    /**
     * Get the definition of the fixer.
     *
     * @return FixerDefinition
     */
    public function getDefinition(): FixerDefinition
    {

        return new FixerDefinition(
            'Adds your copyright header to the top of the file.',
            []
        );
    }

    /**
     * Get the name of the fixer.
     *
     * @return string
     */
    public function getName(): string
    {

        return 'WvnderlabAgency/copyright_header';
    }

    /**
     * Get the priority of the fixer.
     *
     * @return int
     */
    public function getPriority(): int
    {

        return -30;
    }

    /**
     * Check if the fixer is a candidate for the given tokens.
     *
     * @param Tokens $tokens
     * @return bool
     */
    public function isCandidate(Tokens $tokens): bool
    {

        return $tokens->isAnyTokenKindsFound([T_OPEN_TAG]);
    }

    /**
     * Specifies whether the fixer is risky or not.
     *
     * @return bool
     */
    public function isRisky(): bool
    {

        return false;
    }

    /**
     * Returns true if the file is supported by this fixer.
     *
     * @param SplFileInfo $file
     * @return bool
     */
    public function supports(SplFileInfo $file): bool
    {

        return pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'php';
    }

    /**
     * Apply the fixer to the given file.
     *
     * @param SplFileInfo $file
     * @param Tokens $tokens
     * @return void
     */
    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        // only proceed if file starts with <?php
        if (!$tokens[0]->isGivenKind(T_OPEN_TAG)) {
            return;
        }

        $index = 1;
        $endOfLines = 1;

        // check if there is an space after the open tag
        if ($tokens[1]->isWhitespace()) {
            $index = 2;
            $endOfLines = 0;
        }

        // determine insertion index (after open tag or declare)
        $firstMeaningful = $tokens->getNextMeaningfulToken(0);
        if ($tokens[$firstMeaningful]->isGivenKind(T_DECLARE)) {
            $semicolon = $tokens->getNextTokenOfKind($firstMeaningful, [';']);
            if (null !== $semicolon) {
                $index = $tokens->getNextMeaningfulToken($semicolon);
                $endOfLines = 0;
            }
        }

        // prevent duplicate header
        $prevIndex = $tokens->getPrevNonWhitespace($index);
        if ($tokens[$index]->isGivenKind(T_DOC_COMMENT) || ($prevIndex && $tokens[$prevIndex]->isGivenKind(T_DOC_COMMENT))) {
            return;
        }

        extract([
            'year' => date('Y'),
        ]);

        $copyright = include __DIR__ . '/../copyright.php';

        // insert whitespace and doc comment tokens
        if ($endOfLines > 0) {
            $tokens->insertAt($index, new Token([T_WHITESPACE, str_repeat(PHP_EOL, $endOfLines)]));
        }
        $tokens->insertAt(
            $endOfLines > 0 ? $index + 1 : $index,
            new Token([T_DOC_COMMENT, $copyright])
        );
    }
}
