<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use SystemEventManager;
use TimePeriodWithoutWeekEnd;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue;
use Tracker_Chart_Data_Burndown;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_FormElement_Field_Burndown;
use Tracker_FormElementFactory;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_BurndownTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Tracker
     */
    private $tracker;

    /**
     * @var Tracker_FormElement_Field_Burndown
     */
    private $burndown_field;

    /**
     * @var Tracker_Artifact
     */
    private $artifact;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var Tracker_Artifact_ChangesetValue
     */
    private $changesetValue;

    private $tracker_id;
    /**
     * @var Mockery\MockInterface|Tracker_Artifact
     */
    private $sprint;
    /**
     * @var int
     */
    private $sprint_tracker_id;
    /**
     * @var Mockery\MockInterface|\Tracker
     */
    private $sprint_tracker;
    /**
     * @var Mockery\MockInterface|\Tracker_FormElement_Field_Date
     */
    private $start_date_field;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracker    = \Mockery::spy(\Tracker::class);
        $this->tracker_id = 101;
        $this->tracker->shouldReceive('getId')->andReturn($this->tracker_id);

        $this->artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->form_element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        Tracker_FormElementFactory::setInstance($this->form_element_factory);

        $this->burndown_field = \Mockery::mock(\Tracker_FormElement_Field_Burndown::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $logger = \Mockery::spy(BurndownLogger::class);
        $this->burndown_field->shouldReceive('getLogger')->andReturn($logger);
        $this->burndown_field->shouldReceive('fetchBurndownReadOnly')
            ->with($this->artifact)
            ->andReturn('<div id="burndown-chart"></div>');
        $this->burndown_field->shouldReceive('isCacheBurndownAlreadyAsked')->andReturnFalse();

        $this->user = \Mockery::spy(\PFUser::class);

        SystemEventManager::setInstance(\Mockery::spy(SystemEventManager::class));

        $this->sprint            = \Mockery::spy(\Tracker_Artifact::class);
        $this->sprint_tracker_id = 113;
        $this->sprint_tracker    = \Mockery::spy(\Tracker::class);
        $this->sprint_tracker->shouldReceive("getId")->andReturn($this->sprint_tracker_id);
        $this->sprint->shouldReceive("getTracker")->andReturn($this->sprint_tracker);
    }

    protected function tearDown(): void
    {
        SystemEventManager::clearInstance();
        Tracker_FormElementFactory::clearInstance();
        parent::tearDown();
    }

    private function getAStartDateField($value)
    {
        $start_date_field           = Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $start_date_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_Date::class);
        $start_date_changeset_value->shouldReceive('getTimestamp')->andReturn($value);

        $this->artifact->shouldReceive('getValue')
            ->with($start_date_field)
            ->andReturn($start_date_changeset_value);

        $this->form_element_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->tracker,
                $this->user,
                'start_date'
            )->andReturn(
                $start_date_field
            );

        $start_date_field->shouldReceive('userCanRead')->andReturnTrue();
        $this->tracker->shouldReceive('hasFormElementWithNameAndType')
            ->with('start_date', array('date'))
            ->andReturnTrue();
    }

    private function getADurationField($value)
    {
        $duration_field = Mockery::spy(\Tracker_FormElement_Field_Integer::class);

        $duration_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_Date::class);
        $duration_changeset_value->shouldReceive('getValue')->andReturn($value);

        $this->artifact->shouldReceive('getValue')
            ->with($duration_field)
            ->andReturn($duration_changeset_value);

        $this->form_element_factory->shouldReceive('getNumericFieldByNameForUser')
            ->with(
                $this->tracker,
                $this->user,
                'duration'
            )->andReturn(
                $duration_field
            );

        $duration_field->shouldReceive('userCanRead')->andReturnTrue();
        $this->tracker->shouldReceive('hasFormElementWithNameAndType')
            ->with('duration', array('int', 'float', 'computed'))
            ->andReturnTrue();
    }

    public function testItRendersAD3BurndownMontPointWhenBurndownHasAStartDateAndADuration()
    {
        $this->user->shouldReceive('isAdmin')->andReturnTrue();
        $this->burndown_field->shouldReceive('getCurrentUser')->andReturn($this->user);

        $timestamp = mktime(0, 0, 0, 20, 12, 2016);
        $this->getAStartDateField($timestamp);

        $duration = 5;
        $this->getADurationField($duration);

        $result = $this->burndown_field->fetchArtifactValueReadOnly($this->artifact, $this->changesetValue);

        $this->assertEquals(
            '<div id="burndown-chart"></div>',
            $result
        );
    }

    public function testItRendersAJPGraphBurndownErrorWhenUserCantReadBurndownField()
    {
        $this->burndown_field->shouldReceive("getCurrentUser")->andReturn($this->user);
        $this->burndown_field->shouldReceive("userCanRead")->andReturn(false);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);
        $this->expectExceptionMessage('You are not allowed to access this field.');

        $this->burndown_field->fetchBurndownImage($this->artifact, $this->user);
    }

    public function testButtonForceCacheGenerationIsNotPresentWhenStartDateIsNotSet()
    {
        $this->user->shouldReceive('isAdmin')->andReturnTrue();
        $this->burndown_field->shouldReceive('getCurrentUser')->andReturn($this->user);

        $timestamp = null;
        $this->getAStartDateField($timestamp);

        $duration = 5;
        $this->getADurationField($duration);

        $this->burndown_field->shouldReceive('renderPresenter')->andReturn('<div id="burndown-chart"></div>');

        $result = $this->burndown_field->fetchArtifactValueReadOnly($this->artifact, $this->changesetValue);

        $this->assertSame('<div id="burndown-chart"></div>', $result);
    }

    public function testButtonForceCacheGenerationIsNotRenderedWhenDurationIsNotSet()
    {
        $this->user->shouldReceive('isAdmin')->andReturnTrue();
        $this->burndown_field->shouldReceive('getCurrentUser')->andReturn($this->user);

        $timestamp = mktime(0, 0, 0, 20, 12, 2016);
        $this->getAStartDateField($timestamp);

        $duration = null;
        $this->getADurationField($duration);

        $this->burndown_field->shouldReceive('renderPresenter')->andReturn('<div id="burndown-chart"></div>');

        $result = $this->burndown_field->fetchArtifactValueReadOnly($this->artifact, $this->changesetValue);

        $this->assertSame('<div id="burndown-chart"></div>', $result);
    }

    public function testItDisplaysTheOldJPGraph()
    {
        $timestamp = mktime(0, 0, 0, 7, 3, 2011);
        $duration  = 5;

        $time_period   = TimePeriodWithoutWeekEnd::buildFromDuration($timestamp, $duration);
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period);

        $burndown_view = \Mockery::spy(\Tracker_Chart_BurndownView::class);

        $this->burndown_field->shouldReceive('getBurndown')
            ->with($burndown_data)
            ->andReturn($burndown_view);

        $this->burndown_field->shouldReceive('userCanRead')->andReturnTrue();

        $this->burndown_field->shouldReceive('buildBurndownDataForLegacy')
            ->with($this->user, $this->sprint)
            ->andReturn($burndown_data);

        $this->burndown_field->shouldReceive('getLogger')->andReturn(\Mockery::spy(BurndownLogger::class));

        $start_date_field = Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $this->form_element_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->sprint_tracker,
                $this->user,
                'start_date'
            )->andReturn(
                $start_date_field
            );

        $duration_field = Mockery::spy(\Tracker_FormElement_Field_Integer::class);
        $this->form_element_factory->shouldReceive('getNumericFieldByNameForUser')
            ->with(
                $this->sprint_tracker,
                $this->user,
                'duration'
            )->andReturn(
                $duration_field
            );

        $burndown_view->shouldReceive('display')->once();

        $this->burndown_field->fetchBurndownImage($this->sprint, $this->user);
    }
}
