<?php

/**
 * Copyright 2020 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupSelfService\SelfServiceBundle\Tests\Service\RemoteVetting\Dto;

use PHPUnit\Framework\TestCase;
use SAML2\XML\saml\NameID;
use stdClass;
use Surfnet\SamlBundle\SAML2\Attribute\Attribute;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDefinition;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeSet;
use Surfnet\StepupSelfService\SelfServiceBundle\Exception\AssertionFailedException;
use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Dto\AttributeListDto;

class AttributeListDtoTest extends TestCase
{
    /**
     * @dataProvider provideValidAttributes
     */
    public function test_allows_string_and_array_of_string_values($nameId, $attributes, $expected)
    {
        $attribute = new AttributeListDto($attributes, $nameId);
        $this->assertEquals($expected, $attribute->serialize());
    }

    /**
     * @dataProvider provideValidAttributes
     */
    public function test_allows_loading_attributes_from_serialised_string($nameId, $attributes, $expected)
    {
        $attribute = new AttributeListDto($attributes, $nameId);
        $this->assertEquals($expected, $attribute->serialize());

        $newAttribute = AttributeListDto::deserialize($attribute->serialize());
        $this->assertEquals($expected, $newAttribute->serialize());
    }

    /**
     * @dataProvider provideInvalidAttributes
     */
    public function test_disallows_non_scalar_types($nameId, $attributes, $expectedExceptionMessage)
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $attribute = new  AttributeListDto($attributes, $nameId);
    }

    public function test_can_be_created_from_attribute_set()
    {
        $attributes = [
            new Attribute(new AttributeDefinition('firstName', 'urn:mace:firstName', 'urn:oid:0.2.1'), ['John']),
            new Attribute(new AttributeDefinition('lastName', 'urn:mace:lastName', 'urn:oid:0.2.2'), ['Doe']),
            new Attribute(new AttributeDefinition('isMemberOf', 'urn:mace:isMemberOf', 'urn:oid:0.2.7'), ['team-a', 'a-team']),
            new Attribute(new AttributeDefinition('nameId', 'urn:mace:nameId', 'urn:oid:0.2.7'), [NameID::fromArray(['Value' => 'johndoe.example.com', 'Format' => 'unspecified'])]),
        ];
        $set = AttributeSet::create($attributes);

        $dto = AttributeListDto::fromAttributeSet($set);

        $convertedAttributes = $dto->getAttributeCollection()->getAttributes();

        $this->assertCount(3, $dto->getAttributeCollection());
        $this->assertEquals(['John'], $convertedAttributes['firstName']->getValue());
        $this->assertEquals(['Doe'], $convertedAttributes['lastName']->getValue());
        $this->assertEquals(['team-a', 'a-team'], $convertedAttributes['isMemberOf']->getValue());
        $this->assertEquals('johndoe.example.com', $dto->getNameId());
    }

    public function provideValidAttributes()
    {
        yield ['foobar', ['foo' => ['bar']], '{"nameId":"foobar","attributes":{"foo":["bar"]}}'];
        yield ['foobar', ['foo' => ['bar', 'foo2' => '2']], '{"nameId":"foobar","attributes":{"foo":["bar","2"]}}'];
        yield ['foobar', ['foo' => ['bar', 'foo2' => 'bar2', 'foo3' => 'bar3']], '{"nameId":"foobar","attributes":{"foo":["bar","bar2","bar3"]}}'];
        yield ['foobar', ['foo' => ['bar'], 'foo2' => ['bar', 'bar2']], '{"nameId":"foobar","attributes":{"foo":["bar"],"foo2":["bar","bar2"]}}'];
    }

    public function provideInvalidAttributes()
    {
        yield ['foobar', [true, 2], 'The $name of an Attribute must be a scalar value'];
        yield ['foobar', [true], 'The $name of an Attribute must be a scalar value'];
        yield ['foobar', [['foobar']], 'The $name of an Attribute must be a scalar value'];
        yield ['foobar', [['foo'=>'bar']], 'The $name of an Attribute must be a scalar value'];
        yield ['foobar', ['valid', new stdClass()], 'The $name of an Attribute must be a scalar value'];

        yield ['foobar', ["foobar" => 0], 'The $value of an Attribute must be an array with strings'];
        yield ['foobar', ["foobar" => "0"], 'The $value of an Attribute must be an array with strings'];
        yield ['foobar', ["foobar" => [0]], 'The $value of an Attribute must be an array with strings'];
        yield ['foobar', ["foobar" => [[]]], 'The $value of an Attribute must be an array with strings'];

        yield [1, ['foobar'], 'The $nameId in an AttributeListDto must be a string value'];
        yield [false, ['foobar'], 'The $nameId in an AttributeListDto must be a string value'];
        yield [['foobar'], ['foobar'], 'The $nameId in an AttributeListDto must be a string value'];
    }
}
