<?php

namespace MetaSyntactical\CodeSniffer\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;
use Composer\Script\Event;
use FilesystemIterator;
use PHPUnit_Framework_TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ScriptHandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ScriptHandler
     */
    private $testClassName;

    private $stateCurrentDir;

    public function setUp()
    {
        $this->testClassName = ScriptHandler::class;
        $this->stateCurrentDir = getcwd();
        chdir(__DIR__."/..");
        if (!is_dir("build/temp/tests/".getmypid())) {
            mkdir("build/temp/tests/".getmypid()."/.git/hooks", 0777, true);
            mkdir("build/temp/tests/".getmypid()."/vendor/example/coding-standard", 0777, true);
            file_put_contents("build/temp/tests/".getmypid()."/vendor/example/coding-standard/ruleset.xml", "");
        }
        chdir("build/temp/tests/".getmypid());
    }

    public function tearDown()
    {
        chdir(__DIR__."/../build/temp/tests/");
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                getmypid(),
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $path) {
            $path->isDir() && !$path->isLink() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }
        rmdir(getmypid());
        chdir($this->stateCurrentDir);
    }

    public function testThatPreCommitHookIsInsertedOnNonExistingFile()
    {
        self::assertFileNotExists(getcwd()."/.git/hooks/pre-commit", "Precondition failed: Pre-Commit already exists");

        list($event, $package, $io) = $this->mockScriptEvent();
        $io
            ->expects(self::never())
            ->method("writeError")
        ;
        $package
            ->expects(self::once())
            ->method("getExtra")
            ->will(self::returnValue(
                array(
                    "codesniffer" => array(
                        "standard" => array(
                            "dependency" => "example/coding-standard"
                        ),
                    ),
                )
            ))
        ;
        $package
            ->expects(self::any())
            ->method("getTargetDir")
            ->will(self::returnValue("vendor"))
        ;

        $className = $this->testClassName;
        $className::addPhpCsToPreCommitHook($event);

        self::assertFileExists(getcwd()."/.git/hooks/pre-commit");
        self::assertContains(
            "BEGIN:metasyntactical/composer-codesniffer-hooks",
            file_get_contents(getcwd()."/.git/hooks/pre-commit")
        );
        self::assertContains(
            "--standard=vendor/example/coding-standard/ruleset.xml",
            file_get_contents(getcwd()."/.git/hooks/pre-commit")
        );
    }

    public function testThatPreCommitHookIsInsertedOnBlankFile()
    {
        touch(getcwd()."/.git/hooks/pre-commit");
        self::assertFileExists(getcwd()."/.git/hooks/pre-commit", "Precondition failed: Pre-Commit not exists");

        list($event, $package, $io) = $this->mockScriptEvent();
        $io
            ->expects(self::never())
            ->method("writeError")
        ;
        $package
            ->expects(self::once())
            ->method("getExtra")
            ->will(self::returnValue(
                array(
                    "codesniffer" => array(
                        "standard" => array(
                            "dependency" => "example/coding-standard"
                        ),
                    ),
                )
            ))
        ;
        $package
            ->expects(self::any())
            ->method("getTargetDir")
            ->will(self::returnValue("vendor"))
        ;

        $className = $this->testClassName;
        $className::addPhpCsToPreCommitHook($event);

        self::assertFileExists(getcwd()."/.git/hooks/pre-commit");
        self::assertContains(
            "BEGIN:metasyntactical/composer-codesniffer-hooks",
            file_get_contents(getcwd()."/.git/hooks/pre-commit")
        );
        self::assertContains(
            "--standard=vendor/example/coding-standard/ruleset.xml",
            file_get_contents(getcwd()."/.git/hooks/pre-commit")
        );
    }

    public function testThatPreCommitHookIsInsertedOnExistingFileWithContent()
    {
        file_put_contents(
            getcwd()."/.git/hooks/pre-commit",
            <<<EOF
#!/usr/bin/env sh
foobar
EOF
);
        self::assertFileExists(getcwd()."/.git/hooks/pre-commit", "Precondition failed: Pre-Commit not exists");

        list($event, $package, $io) = $this->mockScriptEvent();
        $io
            ->expects(self::never())
            ->method("writeError")
        ;
        $package
            ->expects(self::once())
            ->method("getExtra")
            ->will(self::returnValue(
                array(
                    "codesniffer" => array(
                        "standard" => array(
                            "dependency" => "example/coding-standard"
                        ),
                    ),
                )
            ))
        ;
        $package
            ->expects(self::any())
            ->method("getTargetDir")
            ->will(self::returnValue("vendor"))
        ;

        $className = $this->testClassName;
        $className::addPhpCsToPreCommitHook($event);

        self::assertFileExists(getcwd()."/.git/hooks/pre-commit");
        self::assertContains(
            "BEGIN:metasyntactical/composer-codesniffer-hooks",
            file_get_contents(getcwd()."/.git/hooks/pre-commit")
        );
        self::assertContains(
            "--standard=vendor/example/coding-standard/ruleset.xml",
            file_get_contents(getcwd()."/.git/hooks/pre-commit")
        );
        self::assertContains(
            "foobar",
            file_get_contents(getcwd()."/.git/hooks/pre-commit")
        );
    }

    public function testThatPreCommitHookIsNotInsertedIfAlreadyPresent()
    {
        file_put_contents(
            getcwd()."/.git/hooks/pre-commit",
            <<<EOF
#!/usr/bin/env sh
# BEGIN:metasyntactical/composer-codesniffer-hooks
EOF
        );
        self::assertFileExists(getcwd()."/.git/hooks/pre-commit", "Precondition failed: Pre-Commit not exists");
        self::assertContains(
            "BEGIN:metasyntactical/composer-codesniffer-hooks",
            file_get_contents(getcwd()."/.git/hooks/pre-commit")
        );

        list($event, $package, $io) = $this->mockScriptEvent();
        $io
            ->expects(self::never())
            ->method("writeError")
        ;
        $package
            ->expects(self::once())
            ->method("getExtra")
            ->will(self::returnValue(
                array(
                    "codesniffer" => array(
                        "standard" => array(
                            "dependency" => "example/coding-standard"
                        ),
                    ),
                )
            ))
        ;

        $className = $this->testClassName;
        $className::addPhpCsToPreCommitHook($event);

        self::assertFileExists(getcwd()."/.git/hooks/pre-commit");
        self::assertContains(
            "BEGIN:metasyntactical/composer-codesniffer-hooks",
            file_get_contents(getcwd()."/.git/hooks/pre-commit")
        );
        self::assertNotContains(
            "--standard=vendor/example/coding-standard/ruleset.xml",
            file_get_contents(getcwd()."/.git/hooks/pre-commit")
        );
    }

    public function testThatHookDoesNothingUnlessStandardDefined()
    {
        list($event, $package, $io) = $this->mockScriptEvent();
        $io
            ->expects(self::once())
            ->method("writeError");

        $className = $this->testClassName;
        $className::addPhpCsToPreCommitHook($event);
    }

    /**
     * @return Event
     */
    private function mockScriptEvent()
    {
        $config = new Config(false, getcwd());
        $config->merge('vendor-dir', 'vendor');
        $composer = new Composer();
        $composer->setConfig($config);
        $package = $this
            ->getMockForAbstractClass(
                RootPackageInterface::class
            );
        $composer->setPackage($package);
        $io = $this
            ->getMockForAbstractClass(
                IOInterface::class
            );
        $event = new Event("foo", $composer, $io, true);

        return array($event, $package, $io);
    }
}
