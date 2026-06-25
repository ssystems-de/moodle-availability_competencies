YUI.add('moodle-availability_competencies-form', function (Y, NAME) {

/**
 * Availability competencies - YUI code for plugin form
 *
 * @package    availability_competencies
 * @copyright  2026 Dennis Pfahl, ssystems GmbH <dpfahl@ssystems.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// eslint-disable-next-line camelcase
M.availability_competencies = M.availability_competencies || {};

M.availability_competencies.form = Y.Object(M.core_availability.plugin);

/**
 * @param {Object} params Init params from PHP.
 */
M.availability_competencies.form.initInner = function(competencies) {
    M.availability_competencies.form.competencies = competencies || [];
};

/**
 * @param {Object} json Saved condition data.
 * @return {Y.Node}
 */
M.availability_competencies.form.getNode = function(json) {
    var competencies = M.availability_competencies.form.competencies;
    var html = '<span class="availability-competencies">';
    html += '<label><span class="accesshide">' + M.util.get_string('title', 'availability_competencies') + '</span>';
    html += '<select class="form-select">';
    html += '<option value="">' + M.util.get_string('choosedots', 'moodle') + '</option>';

    var i;
    for (i = 0; i < competencies.length; i++) {
        var selected = '';
        if (json.competencyid && parseInt(json.competencyid, 10) === competencies[i].id) {
            selected = ' selected="selected"';
        }
        html += '<option value="' + competencies[i].id + '"' + selected + '>' +
            Y.Escape.html(competencies[i].name) + '</option>';
    }
    html += '</select></label>';

    if (json.competencyid && !M.availability_competencies.form.isValidCompetency(json.competencyid)) {
        html += ' <span class="badge bg-warning text-dark">' +
            M.util.get_string('invalidcompetency', 'availability_competencies') + '</span>';
    }

    html += '</span>';
    var node = Y.Node.create(html);

    if (!M.availability_competencies.form.addedEvents) {
        M.availability_competencies.form.addedEvents = true;
        var root = Y.one('#fitem_id_availabilityconditionsjson');
        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability-competencies select');
    }

    return node;
};

/**
 * @param {int} competencyid Competency ID.
 * @return {boolean}
 */
M.availability_competencies.form.isValidCompetency = function(competencyid) {
    var competencies = M.availability_competencies.form.competencies;
    var id = parseInt(competencyid, 10);
    var i;
    for (i = 0; i < competencies.length; i++) {
        if (competencies[i].id === id) {
            return true;
        }
    }
    return false;
};

/**
 * @param {Object} value Value object to fill.
 * @param {Y.Node} node Form node.
 */
M.availability_competencies.form.fillValue = function(value, node) {
    var select = node.one('select');
    var selected = parseInt(select.get('value'), 10);
    if (!isNaN(selected) && selected > 0) {
        value.competencyid = selected;
    } else {
        delete value.competencyid;
    }
};

/**
 * @param {string[]} errors Error list.
 * @param {Y.Node} node Form node.
 */
M.availability_competencies.form.fillErrors = function(errors, node) {
    var select = node.one('select');
    if (!select.get('value')) {
        errors.push('availability_competencies:error_selectcompetency');
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event", "escape", "moodle-core_availability-form"]});
