<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the tereference_validators of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\reference;

use TuleapTestCase;

class ReferenceValidatorTest extends TuleapTestCase
{
    /**
     * @var ReferenceValidator
     */
    private $reference_validator;

    public function setUp()
    {
        parent::setUp();

        $this->reference_validator = new ReferenceValidator(
            mock('ReferenceDao'),
            new ReservedKeywordsRetriever(\Mockery::spy(\EventManager::class))
        );
    }

    public function itTestKeywordCharacterValidation()
    {
        $this->assertFalse($this->reference_validator->isValidKeyword("UPPER"));
        $this->assertFalse($this->reference_validator->isValidKeyword("with space"));
        $this->assertFalse($this->reference_validator->isValidKeyword('with$pecialchar'));
        $this->assertFalse($this->reference_validator->isValidKeyword("with/special/char"));
        $this->assertFalse($this->reference_validator->isValidKeyword("with-special"));
        $this->assertFalse($this->reference_validator->isValidKeyword("-begin"));
        $this->assertFalse($this->reference_validator->isValidKeyword("end-"));
        $this->assertFalse($this->reference_validator->isValidKeyword("end "));

        $this->assertTrue($this->reference_validator->isValidKeyword("valid"));
        $this->assertTrue($this->reference_validator->isValidKeyword("valid123"));
        $this->assertTrue($this->reference_validator->isValidKeyword("123"));
        $this->assertTrue($this->reference_validator->isValidKeyword("with_underscore"));
    }

    public function itTestIfKeywordIsReserved()
    {
        $this->assertTrue($this->reference_validator->isReservedKeyword("art"));
        $this->assertTrue($this->reference_validator->isReservedKeyword("cvs"));
        $this->assertFalse($this->reference_validator->isReservedKeyword("artifacts"));
        $this->assertFalse($this->reference_validator->isReservedKeyword("john2"));
    }
}
