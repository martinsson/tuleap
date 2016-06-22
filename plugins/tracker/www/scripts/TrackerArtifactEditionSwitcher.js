/**
 * Copyright (c) Enalean, 2014-2016. All Rights Reserved.
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

var tuleap              = tuleap || { };
tuleap.tracker          = tuleap.tracker || { };
tuleap.tracker.artifact = tuleap.tracker.artifact || { };

(function($) {

tuleap.tracker.artifact.editionSwitcher = function() {

    var pair_fields_toggled = {};

    var init = function() {
        bindClickOnEditableFields();
        bindClickOnAutocomputeInMassChange();
        if ($("#artifact_informations").size() > 0) {
            bindSubmissionBarToFollowups();
            disableWarnBeforeUnloadOnSubmitForm();
            toggleFieldsIfAlreadySubmittedArtifact();
            toggleEmptyMandatoryFields();
        }
    };

    var toggleFieldsIfAlreadySubmittedArtifact = function () {
        if ($('.submitted_artifact').size() > 0) {
            $(".tracker_artifact_field").each(function (index, element){
                if (fieldIsEditable(element)) {
                    toggleField(element);
                }
            });
        }
    };

    var toggleEmptyMandatoryFields = function () {
        $('.editable').each(function() {
            var field = $(this);
            if (field.find('.highlight').size() > 0 && field.find('.empty_value').size() > 0) {
                toggleField(field);
            }
        });
    };

    var disableWarnBeforeUnloadOnSubmitForm = function() {
        $('form').submit(function() {
            window.onbeforeunload = function(){};
        });
    }

    var bindClickOnEditableFields = function() {
        $(".tracker_artifact_field").each(bindField);
    };

    var bindField = function (index, element) {
        if(fieldIsCreatable(element)) {
            bindCreationSwitch(element);
        }

        if (fieldIsEditable(element)) {
            bindEditionSwitch(element);
        }

        return;
    };

    var bindClickOnAutocomputeInMassChange = function() {
        $(".field-masschange.tracker_artifact_field-computed").each(bindEditionBackToAutocomputeMassChange);
    };

    var bindEditionBackToAutocomputeMassChange = function (index, element) {
        $(element).find('.auto-compute').on('click', function () {
            var field_id = $(element).attr('data-field-id');
            switchValueToAutoComputedMode(element, field_id);
            $(element).find('.edition-mass-change').hide();
            $(element).find('.display-mass-change').show();
            $(element).removeClass('in-edition');
        });

        $(element).find('.edit-mass-change-autocompute').on('click', function () {
            var field_id = $(element).attr('data-field-id');
            switchValueToManualMode(element, field_id);
            $(element).find('.edition-mass-change').show();
            $(element).find('.display-mass-change').hide();
            $(element).addClass('in-edition');
        });
    };

    var bindCreationSwitch = function (element) {
        $(element).find('.tracker_formelement_edit').on('click', function (event) {
            event.preventDefault();
            var field_id = $(element).find('.add-field').attr('data-field-id');
            switchValueToManualMode(element, field_id);
            toggleAddiationField(element);
        });

        $(element).find('.auto-compute').on('click', function () {
            var field_id = $(element).find('.add-field').attr('data-field-id');
            switchValueToAutoComputedMode(element, field_id);
            $(element).find('.add-field').hide();
            $(element).find('.auto-computed').show();
            $(element).find('.tracker_hidden_edition_field').hide();
            $(element).on('click');
            $(element).removeClass('in-edition');
        });
    };

    var bindEditionSwitch = function (element) {
        $(element).find('.tracker_formelement_edit').on('click', function (event) {
            event.preventDefault();

            var field_id = $(element).find('.tracker_hidden_edition_field').attr('data-field-id');
            switchValueToManualMode(element, field_id);
            toggleField(element);
            focusField(element);
        });

        $(element).find('.auto-compute').on('click', function () {
            $(element).find('.auto-computed-label').hide();
            $(element).find('.back-to-autocompute').show();
            $(element).find('.tracker_hidden_edition_field').hide();

            var field_id = $(element).find('.tracker_hidden_edition_field').attr('data-field-id');
            switchValueToAutoComputedMode(element, field_id);
            $(element).on('click');
            $(element).removeClass('in-edition');
        });
    };

    var switchValueToManualMode = function (element, field_id) {
        var field_computed_is_autocomputed = document.getElementsByName("artifact[" + field_id + "][is_autocomputed]");
        if (field_computed_is_autocomputed[0] !== undefined) {
            field_computed_is_autocomputed[0].value = '0';
        }
    };

    var switchValueToAutoComputedMode = function (element, field_id) {
        var field_computed_manual_value    = document.getElementsByName("artifact[" + field_id + "][manual_value]");
        var field_computed_is_autocomputed = document.getElementsByName("artifact[" + field_id + "][is_autocomputed]");
        if (field_computed_manual_value[0] !== undefined && field_computed_is_autocomputed[0] !== undefined) {
            field_computed_manual_value[0].value    = null;
            field_computed_is_autocomputed[0].value = '1';
        }
    };

    var toggleAddiationField = function (element) {
        $(element).addClass('in-edition');
        $(element).find('.add-field').show();
        $(element).find('.auto-computed').hide();
        $(element).off('click');
    };

    var toggleField = function (element) {
        removeReadOnlyElements(element);
        removeUnwrappedText(element);
        $(element).addClass('in-edition');
        $(element).find('.tracker_hidden_edition_field').show();
        $(element).find('.auto-computed-label').hide();
        $(element).find('.back-to-autocompute').hide();
        $(element).off('click');
        toggleDependencyIfAny(element);
        toggleSubmissionBar();
        toggleHiddenImageViewing();
    };

    var focusField = function (element) {
        $(element).find('input[type=text], textarea, .cke').filter(':visible:first').focus();
    };

    var toggleDependencyIfAny = function (element) {
        var field_id = $(element).find('.tracker_hidden_edition_field').attr('data-field-id');

        if (! codendi.tracker.rules_definitions || typeof field_id == 'undefined')  {
            return;
        }

        $(codendi.tracker.rules_definitions).each(function() {
            if (this.source_field == field_id) {
                var target_field    = getTargetField(this.target_field);
                var target_field_id = $(target_field).find('.tracker_hidden_edition_field').attr('data-field-id');

                if (target_field.length > 0 && ! pair_fields_toggled[field_id + '_' + target_field_id]) {
                    pair_fields_toggled[field_id + '_' + target_field_id] = true;
                    toggleField(target_field);
                }
            }
        });
    };

    var getTargetField = function(target_field_id) {
        var field = $(".tracker_artifact_field .tracker_hidden_edition_field[data-field-id="+target_field_id+"]");

        if (field) {
            return $(field).parent(".tracker_artifact_field");
        }
    };

    var removeReadOnlyElements = function (element) {
        $(element).children(":not(.tracker_formelement_label,.tracker_hidden_edition_field, .artifact-link-value-reverse, .tracker_formelement_edit, .auto-computed-label,.back-to-autocompute)").remove();
    };

    var removeUnwrappedText = function (element) {
        $(element).contents().filter(function(){ return this.nodeType == 3; }).remove();
    };

    var fieldIsEditable = function(element) {
        return $(".tracker_hidden_edition_field", element).size() > 0;
    };

    var fieldIsCreatable = function(element) {
        return $(".add-field", element).size() > 0;
    };

    var bindSubmissionBarToFollowups = function () {
        toggleSubmissionBarForCommentInCkeditor();

        $('#tracker_followup_comment_new').on('input propertychange', toggleSubmissionBar);

        $('#rte_format_selectboxnew').on('change', function() {
            toggleSubmissionBarForCommentInCkeditor();
        });

        $('#tracker_artifact_canned_response_sb').on('change', toggleSubmissionBar);
    };

    var toggleSubmissionBarForCommentInCkeditor = function () {
        if (CKEDITOR.instances.tracker_followup_comment_new) {
            CKEDITOR.instances.tracker_followup_comment_new.on('change', toggleSubmissionBar);
        }
    }

    var toggleSubmissionBar = function () {
        if (submissionBarIsAlreadyActive()) {
            removeSubmissionBarIfNeeded();
        }

        displaySubmissionBarIfNeeded();
    };

    var toggleHiddenImageViewing = function () {
        $('a[data-rel^=lytebox]').each(function() {
            $(this).attr('rel', $(this).attr('data-rel'));
        });

        new LyteBox();
    };

    var displaySubmissionBarIfNeeded = function () {
        if (somethingIsEdited()) {
            $('.hidden-artifact-submit-button').slideDown(50);
        }
    };

    var removeSubmissionBarIfNeeded = function () {
        if (somethingIsEdited()) {
            return;
        }
        $('.hidden-artifact-submit-button').slideUp(50);
    };

    var somethingIsEdited = function () {
        return ! nothingIsEdited();
    };

    var nothingIsEdited = function () {
        return followUpIsEmpty() && noFieldIsSwitchedToEdit();
    };

    var noFieldIsSwitchedToEdit = function () {
        if ($('.tracker_artifact_field.in-edition').size() > 0) {
            return false;
        }

        return true;
    };

    var followUpIsEmpty = function () {
        if (CKEDITOR.instances.tracker_followup_comment_new) {
            return ! $.trim(CKEDITOR.instances.tracker_followup_comment_new.getData());
        }

        return ! $.trim($("#tracker_followup_comment_new").val());
    };

    var submissionBarIsAlreadyActive = function () {
        return $('.hidden-artifact-submit-button:visible').size() > 0;
    };

    return {
        init: init,
        submissionBarIsAlreadyActive: submissionBarIsAlreadyActive
    };
};

$(document).ready(function() {
    var edition_switcher = new tuleap.tracker.artifact.editionSwitcher();
    edition_switcher.init();
});

})(jQuery);
