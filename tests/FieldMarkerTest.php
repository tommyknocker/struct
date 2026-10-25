<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\tools\FieldMarker;

final class FieldMarkerTest extends TestCase
{
    public function testUnrelatedStructClassIsUntouched(): void
    {
        $source = '<?php namespace Foreign; class Struct {} final class Other extends Struct { public readonly string $name; }';
        $this->assertSame($source, (new FieldMarker())->addMarkers($source));
    }

    public function testUnresolvedGlobalStructIsUntouched(): void
    {
        $source = '<?php class Other extends Struct { public readonly string $name; }';
        $this->assertSame($source, (new FieldMarker())->addMarkers($source));
    }

    public function testImportsInterfacesAndCommentsAreResolved(): void
    {
        $source = <<<'SOURCE'
            <?php
            namespace Example;
            use tommyknocker\struct\Struct as Base;
            final class Model extends /* parent */ Base implements \Stringable {
                public readonly string $name;
                public function __toString(): string { return $this->name; }
            }
            SOURCE;
        $result = (new FieldMarker())->addMarkers($source);
        $this->assertSame(1, substr_count($result, '#[\\tommyknocker\\struct\\Field]'));
        $this->assertSame($result, (new FieldMarker())->addMarkers($result));
    }

    public function testGroupedImportsAndSameFileInheritance(): void
    {
        $source = <<<'SOURCE'
            <?php
            namespace Example;
            use tommyknocker\struct\{Struct as Base, Field};
            class ParentModel extends Base { public readonly int $id; }
            final class ChildModel extends ParentModel { public readonly string $name; }
            SOURCE;
        $result = (new FieldMarker())->addMarkers($source);
        $this->assertSame(2, substr_count($result, '#[\\tommyknocker\\struct\\Field]'));
    }

    public function testNamespaceAliasCanPrefixAParentName(): void
    {
        $source = '<?php namespace App; use tommyknocker\struct as Data; class Model extends Data\Struct { public readonly int $id; }';
        $this->assertStringContainsString('#[\\tommyknocker\\struct\\Field]', (new FieldMarker())->addMarkers($source));
    }

    public function testBracketedNamespaceImportsDoNotLeak(): void
    {
        $source = <<<'SOURCE'
            <?php
            namespace First {
                use tommyknocker\struct\Struct;
                class A extends Struct { public readonly int $id; }
            }
            namespace Second {
                use Foreign\Struct;
                class B extends Struct { public readonly int $id; }
            }
            SOURCE;
        $result = (new FieldMarker())->addMarkers($source);
        $this->assertSame(1, substr_count($result, '#[\\tommyknocker\\struct\\Field]'));
    }

    public function testExistingAttributesDoNotAffectTheFollowingMember(): void
    {
        $source = <<<'SOURCE'
            <?php
            use tommyknocker\struct\Struct;
            class Model extends Struct {
                #[SomeAttribute]
                private function helper(): void {}
                public readonly int $id;
                #[OtherAttribute]
                public readonly string $name;
            }
            SOURCE;
        $result = (new FieldMarker())->addMarkers($source);
        $this->assertSame(1, substr_count($result, '#[\\tommyknocker\\struct\\Field]'));
    }

    public function testClosureUsesAndClassNameConstantsAreNotDeclarations(): void
    {
        $source = <<<'SOURCE'
            <?php
            namespace App;
            use tommyknocker\struct\Struct;
            $value = Struct::class;
            $fn = function () use ($value) { return $value; };
            class Model extends Struct { public readonly int $id; }
            SOURCE;
        $result = (new FieldMarker())->addMarkers($source);
        $this->assertSame(1, substr_count($result, '#[\\tommyknocker\\struct\\Field]'));
    }
}
