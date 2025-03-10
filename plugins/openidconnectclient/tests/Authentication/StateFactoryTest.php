<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
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

use Tuleap\OpenIDConnectClient\Authentication\StateFactory;

require_once(__DIR__ . '/../bootstrap.php');

class StateFactoryTest extends TuleapTestCase
{
    public function itKeepsSameKey()
    {
        $random_number_generator = new RandomNumberGenerator();

        $state_factory_1 = new StateFactory($random_number_generator);
        $state_factory_2 = new StateFactory($random_number_generator);
        $state_1_1       = $state_factory_1->createState(1);
        $state_1_2       = $state_factory_1->createState(2);
        $state_2         = $state_factory_2->createState(1);

        $this->assertEqual($state_1_1->getSecretKey(), $state_1_2->getSecretKey());
        $this->assertEqual($state_1_1->getSecretKey(), $state_2->getSecretKey());
    }

    public function itKeepsSameNonce()
    {
        $random_number_generator = new RandomNumberGenerator();

        $state_factory_1 = new StateFactory($random_number_generator);
        $state_factory_2 = new StateFactory($random_number_generator);
        $state_1_1       = $state_factory_1->createState(1);
        $state_1_2       = $state_factory_1->createState(2);
        $state_2         = $state_factory_2->createState(1);

        $this->assertEqual($state_1_1->getNonce(), $state_2->getNonce());
        $this->assertEqual($state_1_1->getNonce(), $state_1_2->getNonce());
    }

    public function itCreatesStateWithGivenParameters()
    {
        $value = 1234;

        $random_number_generator = new RandomNumberGenerator();
        $state_factory           = new StateFactory($random_number_generator);
        $state                   = $state_factory->createState($value);

        $this->assertEqual($value, $state->getProviderId());
    }
}
