<?php

namespace WvnderlabAgency\CopyrightHeader;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

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

        return $tokens->isMonolithicPhp() && !$tokens->isTokenKindFound(T_OPEN_TAG_WITH_ECHO);
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
        $copyright = $this->getCopyright();

        $newIndex = $this->findCopyrightHeaderInsertionIndex($tokens, 'after_declare_strict');
        $currentIndex = $this->findCopyrightHeaderCurrentIndex($tokens, $newIndex - 1);

        if ($currentIndex === null) {
            $this->insertCopyrightHeader($tokens, $newIndex);
        } else {
            $currentCopyright = $tokens[$currentIndex]->getContent();
            $sameContent = $currentCopyright === $copyright;

            if (!$sameContent) {
                $this->removeCopyrightHeader($tokens, $newIndex);
                $this->insertCopyrightHeader($tokens, $newIndex);
            }
        }
    }

    /**
     * Find the current index of the copyright header in the tokens.
     *
     * @param Tokens $tokens
     * @param int $headerNewIndex
     * @return int|null
     */
    private function findCopyrightHeaderCurrentIndex(Tokens $tokens, int $headerNewIndex): ?int
    {
        $copyright = $this->getCopyright();
        $index = $tokens->getNextNonWhitespace($headerNewIndex);

        if ($index === null || !$tokens[$index]->isComment()) {

            return null;
        }

        $next = $index + 1;

        if (!isset($tokens[$next]) || !$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {

            return $index;
        }

        if ($tokens[$next]->isWhitespace()) {
            if (!Preg::match('/^\h*\R\h*$/D', $tokens[$next]->getContent())) {

                return $index;
            }

            $next++;
        }

        if (!isset($tokens[$next]) || !$tokens[$next]->isClassy() && !$tokens[$next]->isGivenKind(T_FUNCTION)) {

            return $index;
        }

        if ($copyright === $tokens[$next]->getContent()) {

            return $index;
        }

        return null;
    }

    /**
     * Find the index where the copyright header should be inserted.
     *
     * @param Tokens $tokens
     * @param string $location
     * @return int
     */
    private function findCopyrightHeaderInsertionIndex(Tokens $tokens, string $location): int
    {
        $openTagIndex = $tokens[0]->isGivenKind(T_INLINE_HTML) ? 1 : 0;

        if ('after_open' === $location) {

            return $openTagIndex + 1;
        }

        $index = $tokens->getNextMeaningfulToken($openTagIndex);

        if (null === $index) {

            return $openTagIndex + 1; // file without meaningful tokens but an open tag, comment should always be placed directly after the open tag
        }

        if (!$tokens[$index]->isGivenKind(T_DECLARE)) {

            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($index);

        if (null === $next || !$tokens[$next]->equals('(')) {

            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);

        if (null === $next || !$tokens[$next]->equals([T_STRING, 'strict_types'], false)) {

            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);

        if (null === $next || !$tokens[$next]->equals('=')) {

            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);

        if (null === $next || !$tokens[$next]->isGivenKind(T_LNUMBER)) {

            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);

        if (null === $next || !$tokens[$next]->equals(')')) {

            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);

        if (null === $next || !$tokens[$next]->equals(';')) { // don't insert after close tag

            return $openTagIndex + 1;
        }

        return $next + 1;
    }

    /**
     * Fix the whitespace around the copyright header.
     *
     * @param Tokens $tokens
     * @param int $index
     * @return void
     */
    protected function fixWhiteSpaceAroundCopyrightHeader(Tokens $tokens, int $index): void
    {
        $lineEnding = "\n";

        $expectedLineCount = $tokens->getNextMeaningfulToken($index) !== null
            ? 2
            : 1;

        if ($index === count($tokens) - 1) {
            $tokens->insertAt($index + 1, new Token([T_WHITESPACE, str_repeat($lineEnding, $expectedLineCount)]));
        } else {
            $lineBreakCount = $this->getLineBreakCount($tokens, $index, 1);

            if ($lineBreakCount < $expectedLineCount) {
                $missing = str_repeat($lineEnding, $expectedLineCount - $lineBreakCount);

                if ($tokens[$index + 1]->isWhitespace()) {
                    $tokens[$index + 1] = new Token([\T_WHITESPACE, $missing . $tokens[$index + 1]->getContent()]);
                } else {
                    $tokens->insertAt($index + 1, new Token([\T_WHITESPACE, $missing]));
                }
            } elseif ($lineBreakCount > $expectedLineCount && $tokens[$index + 1]->isWhitespace()) {
                $newLinesToRemove = $lineBreakCount - $expectedLineCount;
                $tokens[$index + 1] = new Token([
                    \T_WHITESPACE,
                    Preg::replace("/^\\R{{$newLinesToRemove}}/", '', $tokens[$index + 1]->getContent()),
                ]);
            }
        }

        // fix lines before header comment
        $expectedLineCount = 2;
        $prev = $tokens->getPrevNonWhitespace($index);

        $regex = '/\h$/';

        if ($tokens[$prev]->isGivenKind(\T_OPEN_TAG) && Preg::match($regex, $tokens[$prev]->getContent())) {
            $tokens[$prev] = new Token([\T_OPEN_TAG, Preg::replace($regex, $lineEnding, $tokens[$prev]->getContent())]);
        }

        $lineBreakCount = $this->getLineBreakCount($tokens, $index, -1);

        if ($lineBreakCount < $expectedLineCount) {
            // because of the way the insert index was determined for header comment there cannot be an empty token here
            $tokens->insertAt($index, new Token([T_WHITESPACE, str_repeat($lineEnding, $expectedLineCount - $lineBreakCount)]));
        }
    }

    /**
     * Get the copyright header content.
     *
     * @return non-empty-string
     */
    protected function getCopyright(): string
    {
        extract([
            'year' => date('Y'),
        ]);

        return include __DIR__ . '/../copyright.php';
    }

    /**
     * Get the number of line breaks in the whitespace around the specified index.
     *
     * @param Tokens $tokens
     * @param int $index
     * @param int $direction
     * @return int
     */
    protected function getLineBreakCount(Tokens $tokens, int $index, int $direction): int
    {
        $whitespace = '';

        for ($index += $direction; isset($tokens[$index]); $index += $direction) {
            $token = $tokens[$index];

            if ($token->isWhitespace()) {
                $whitespace .= $token->getContent();

                continue;
            }

            if (-1 === $direction && $token->isGivenKind(T_OPEN_TAG)) {
                $whitespace .= $token->getContent();
            }

            if ('' !== $token->getContent()) {
                break;
            }
        }

        return substr_count($whitespace, "\n");
    }

    /**
     * Insert the copyright header at the specified index.
     *
     * @param Tokens $tokens
     * @param int $index
     */
    protected function insertCopyrightHeader(Tokens $tokens, int $index): void
    {
        $copyright = $this->getCopyright();

        $tokens->insertAt(
            $index,
            new Token([T_DOC_COMMENT, $copyright])
        );

        $this->fixWhiteSpaceAroundCopyrightHeader($tokens, $index);
    }

    /**
     * Remove the copyright header at the specified index.
     *
     * @param Tokens $tokens
     * @param int $index
     */
    private function removeCopyrightHeader(Tokens $tokens, int $index): void
    {
        $prevIndex = $index - 1;
        $prevToken = $tokens[$prevIndex];
        $newlineRemoved = false;

        if ($prevToken->isWhitespace()) {
            $content = $prevToken->getContent();

            if (Preg::match('/\R/', $content)) {
                $newlineRemoved = true;
            }

            $content = Preg::replace('/\R?\h*$/', '', $content);

            $tokens->ensureWhitespaceAtIndex($prevIndex, 0, $content);
        }

        $nextIndex = $index + 1;
        $nextToken = $tokens[$nextIndex] ?? null;

        if (!$newlineRemoved && null !== $nextToken && $nextToken->isWhitespace()) {
            $content = Preg::replace('/^\R/', '', $nextToken->getContent());

            $tokens->ensureWhitespaceAtIndex($nextIndex, 0, $content);
        }

        $tokens->clearTokenAndMergeSurroundingWhitespace($index);
    }
}
