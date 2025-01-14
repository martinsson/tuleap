<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../../../bootstrap.php';

class Git_Driver_Gerrit_Template_TemplateProcessorTest extends TuleapTestCase
{

    /** @var Git_Driver_Gerrit_Template_TemplateProcessor */
    private $template_processor;

    /** @var Git_Driver_Gerrit_Template_Template */
    private $template;

    /** @var Project */
    private $project;

    /** @var string */
    private $project_name = 'someProject';

    public function setUp()
    {

        $this->template_processor = new Git_Driver_Gerrit_Template_TemplateProcessor();
        $this->template           = new Git_Driver_Gerrit_Template_Template(1, 2, 'wathevername', 'whateverecontent');
        $this->project            = stub('Project')->getUnixName()->returns($this->project_name);
    }

    public function itDoesntChangeAnythingIfTemplateHasNoVariable()
    {
        $template_content = "this is some template content without variables";

        $this->template->setContent($template_content);

        $processed = $this->template_processor->processTemplate($this->template, $this->project);

        $this->assertEqual($this->template->getContent(), $processed);
    }

    public function itReplacesTheProjectNameByTheAppropriateVariable()
    {
        $template_content = "this %projectname% should be replaced by the project name.

            this one %projectname% too!";

        $expected = "this $this->project_name should be replaced by the project name.

            this one $this->project_name too!";

        $this->template->setContent($template_content);

        $processed = $this->template_processor->processTemplate($this->template, $this->project);

        $this->assertEqual($expected, $processed);
    }

    public function itDoesntReplaceIrrevelantVariables()
    {
        $template_content = "this %projectid% should be replaced by the project name.

            this one %projectid% too!";

        $this->template->setContent($template_content);

        $processed = $this->template_processor->processTemplate($this->template, $this->project);

        $this->assertEqual($template_content, $processed);
    }
}
