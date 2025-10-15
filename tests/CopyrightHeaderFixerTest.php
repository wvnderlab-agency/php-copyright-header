<?php

namespace WvnderlabAgency\CopyrightHeader\Tests;

use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SplFileInfo;
use WvnderlabAgency\CopyrightHeader\CopyrightHeaderFixer;

class CopyrightHeaderFixerTest extends TestCase
{
    protected function getCopyright(): string
    {
        $fixer = new CopyrightHeaderFixer();
        $reflection = new ReflectionClass($fixer);
        $method = $reflection->getMethod('getCopyright');
        $method->setAccessible(true);

        /** @var non-empty-string $copyright */
        $copyright = $method->invoke($fixer);

        return $copyright;
    }

    #[Test]
    public function adds_header_when_php_tag_present(): void
    {
        $fixer = new CopyrightHeaderFixer();
        $copyright = $this->getCopyright();
        $input = "<?php\n\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        // use fix method to apply the fixer, cause applyFix is protected
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $output = $tokens->generateCode();

        $this->assertStringStartsWith("<?php\n\n/**", $output);
        $this->assertStringContainsString($copyright, $output);
    }

    #[Test]
    public function adds_header_when_php_tag_present_with_spacing(): void
    {
        $fixer = new CopyrightHeaderFixer();
        $copyright = $this->getCopyright();
        $input = "<?php \n\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        // use fix method to apply the fixer, cause applyFix is protected
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $output = $tokens->generateCode();

        $this->assertStringStartsWith("<?php\n\n/**", $output);
        $this->assertStringContainsString($copyright, $output);
    }

    #[Test]
    public function adds_header_when_declare_strict_types_present(): void
    {
        $fixer = new CopyrightHeaderFixer();
        $copyright = $this->getCopyright();
        $input = "<?php declare(strict_types=1);\n\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        // use fix method to apply the fixer, cause applyFix is protected
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $output = $tokens->generateCode();

        $this->assertStringStartsWith("<?php declare(strict_types=1);\n\n/**", $output);
        $this->assertStringContainsString($copyright, $output);
    }

    #[Test]
    public function adds_header_when_declare_strict_types_present_with_spacing(): void
    {
        $fixer = new CopyrightHeaderFixer();
        $copyright = $this->getCopyright();
        $input = "<?php declare(strict_types=1); \n\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        // use fix method to apply the fixer, cause applyFix is protected
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $output = $tokens->generateCode();

        $this->assertStringStartsWith("<?php declare(strict_types=1);\n\n/**", $output);
        $this->assertStringContainsString($copyright, $output);
    }

    #[Test]
    public function does_not_add_duplicate_header_when_run_twice(): void
    {
        $fixer = new CopyrightHeaderFixer();
        $input = "<?php\n\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        $fixer->fix(new SplFileInfo('test.php'), $tokens);
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $code = $tokens->generateCode();

        $this->assertSame(1, substr_count($code, '/**'));
    }

    #[Test]
    public function does_nothing_when_no_php_tag_present(): void
    {
        $fixer = new CopyrightHeaderFixer();
        $input = "// just a comment\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        $fixer->fix(new SplFileInfo('dummy.php'), $tokens);

        $this->assertSame($input, $tokens->generateCode());
    }

    #[Test]
    public function overwrites_outdated_headers(): void
    {
        $fixer = new CopyrightHeaderFixer();
        $copyright = $this->getCopyright();
        $outdatedCopyright = str_replace(date('Y'), '2020', $copyright);
        $input = "<?php\n{$outdatedCopyright}";
        $tokens = Tokens::fromCode($input);

        // use fix method to apply the fixer, cause applyFix is protected
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $output = $tokens->generateCode();

        $this->assertStringContainsString($copyright, $output);
        $this->assertStringNotContainsString($outdatedCopyright, $output);
    }
}