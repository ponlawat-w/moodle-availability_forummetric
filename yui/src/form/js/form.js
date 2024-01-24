/**
 * @module moodle-availability_forummetric-form
 */
M.availability_forummetric = M.availability_forummetric || {};

/**
 * @class M.availability_forummetric.form
 * @extends M.core_availability.plugin
 */
M.availability_forummetric.form = Y.Object(M.core_availability.plugin);

M.availability_forummetric.form.initInner = function(metrics = [], forums = []) {
    this.metrics = metrics.filter(x => x.metric && x.name);
    this.forums = forums.filter(x => x.id && x.name);
};

function getdateinput(name) {
    let html = '<div><label>';
    html += `<input type="checkbox" name="enable${name}"> ${M.util.get_string(name, 'availability_forummetric')}`;
    html += '<span class="ml-2">'
    html += `<input type="date" class="form-control" name="${name}_date">`
    html += `<input type="time" class="form-control" name="${name}_time">`
    html += '</span>'
    html += '</label></div>';
    return html;
}

M.availability_forummetric.form.getNode = function(json) {
    let html = '<span class="availability-forummetric">';
    html += '<select name="forum" class="form-control">';
    html += `<option value="0">${M.util.get_string('allforums', 'availability_forummetric')}</option>`;
    for (const forum of this.forums) {
        html += `<option value="${forum.id}">${forum.name}</option>`;
    }
    html += '</select>';
    html += `<select name="metric" class="form-control">`;
    for (const metric of this.metrics) {
        html += `<option value="${metric.metric}">${metric.name}</option>`;
    }
    html += `</select>`;
    html += '<select name="condition" class="form-control">';
    html += `<option value="morethan">${M.util.get_string('morethan', 'availability_forummetric')}</option>`
    html += `<option value="lessthan">${M.util.get_string('lessthan', 'availability_forummetric')}</option>`
    html += '</select>';
    html += '<input type="number" name="value" class="form-control" min="0">'
    html += getdateinput('startdate');
    html += getdateinput('enddate');
    html += '</span>';
    const node = Y.Node.create(`<span class="form-inline">${html}</span>`);

    const forum = node.one('select[name=forum]');
    const metric = node.one('select[name=metric]');
    const condition = node.one('select[name=condition]');
    const value = node.one('input[name=value]');

    if (forum) {
        forum.set('value', json.forum ?? 0);
    }
    if (metric) {
        metric.set('value', json.metric ?? 'numreplies');
    }
    if (condition) {
        condition.set('value', json.condition ?? 'morethan');
    }
    if (value) {
        value.set('value', parseInt(json.value ?? 0));
    }

    if (!M.availability_forummetric.form.addedEvents) {
        M.availability_forummetric.form.addedEvents = true;
        const root = Y.one('.availability-field');
        root.delegate('change', () => {
            M.core_availability.form.update();
        }, '.availability-forummetric select,input')
    }

    return node;
};

M.availability_forummetric.form.fillValue = function(value, node) {
    const forumElement = node.one('select[name=forum]');
    const metricElement = node.one('select[name=metric]');
    const conditionElement = node.one('select[name=condition]');
    const valueElement = node.one('input[name=value]');

    value.forum = forumElement?.get('value') ?? null;
    value.metric = metricElement?.get('value') ?? null;
    value.condition = conditionElement?.get('value') ?? null;
    value.value = valueElement?.get('value') ?? null;
};

M.availability_forummetric.form.fillErrors = function(errors, node) {};
