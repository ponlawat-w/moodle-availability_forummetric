YUI.add('moodle-availability_forummetric-form', function (Y, NAME) {

/**
 * @module moodle-availability_forummetric-form
 */
M.availability_forummetric = M.availability_forummetric || {};

/**
 * @class M.availability_forummetric.form
 * @extends M.core_availability.plugin
 */
M.availability_forummetric.form = Y.Object(M.core_availability.plugin);

M.availability_forummetric.form.initInner = function(metrics, forums) {
    this.metrics = (metrics ?? []).filter(x => x.metric && x.name);
    this.forums = (forums ?? []).filter(x => x.id && x.name);
};

function getdateinput(name, show) {
    const display = show ? 'inline-block' : 'none';
    let html = '<label style="display: block;">';
    html += `<input type="checkbox" name="${name}_enabled"> ${M.util.get_string(name, 'availability_forummetric')}`;
    html += `<span data-input="${name}" class="dtinput ml-2" style="display: ${display};">`;
    html += `<input type="date" class="form-control" name="${name}_date">`
    html += `<input type="time" class="form-control" name="${name}_time" step="1">`
    html += '</span>'
    html += '</label>';
    return html;
}

function initiatedateinputevent(node, name) {
    const checkbox = node.one(`input[type=checkbox][name=${name}_enabled]`);
    const inputs = node.one(`span.dtinput[data-input=${name}]`);
    if (!checkbox || !inputs) return;
    checkbox.on('change', () => {
        const display = checkbox.get('checked') ? 'inline-block' : 'none';
        inputs.setStyle('display', display);
    });
}

function initiateengagementevent(node) {
    const select = node.one('select[name=metric]');
    const label = node.one('label[data-identifier=engagementinternational]');
    if (!select || !label) return;
    select.on('change', () => {
        const display = select.get('value').startsWith('maxengagement_') ? 'inline-block' : 'none';
        label.setStyle('display', display);
    });
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
    html += getdateinput('fromdate', json.fromdate?.enabled ?? false);
    html += getdateinput('todate', json.todate?.enabled ?? false);
    html += `<label data-identifier="engagementinternational" style="display: ${json.metric?.startsWith('maxengagement_') ? 'block' : 'none'};">`;
    html += `<input type="checkbox" name="engagementinternational"> ${M.util.get_string('engagement_international', 'availability_forummetric')}`;
    html += '</label>';
    html += '</span>';
    const node = Y.Node.create(`<span class="form-inline">${html}</span>`);

    const forum = node.one('select[name=forum]');
    const metric = node.one('select[name=metric]');
    const condition = node.one('select[name=condition]');
    const value = node.one('input[name=value]');
    const fromdate_enabled = node.one('input[name=fromdate_enabled]');
    const fromdate_date = node.one('input[name=fromdate_date]');
    const fromdate_time = node.one('input[name=fromdate_time]');
    const todate_enabled = node.one('input[name=todate_enabled]');
    const todate_date = node.one('input[name=todate_date]');
    const todate_time = node.one('input[name=todate_time]');
    const engagementinternational = node.one('input[name=engagementinternational]');

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
    if (fromdate_enabled) {
        fromdate_enabled.set('checked', json.fromdate?.enabled ?? false);
    }
    if (fromdate_date) {
        fromdate_date.set('value', json.fromdate?.date ?? null);
    }
    if (fromdate_time) {
        fromdate_time.set('value', json.fromdate?.time ?? null);
    }
    if (todate_enabled) {
        todate_enabled.set('checked', json.todate?.enabled ?? false);
    }
    if (todate_date) {
        todate_date.set('value', json.todate?.date ?? null);
    }
    if (todate_time) {
        todate_time.set('value', json.todate?.time ?? null);
    }
    if (engagementinternational) {
        engagementinternational.set('checked', json.engagementinternational ? true : false);
    }

    if (!M.availability_forummetric.form.addedEvents) {
        M.availability_forummetric.form.addedEvents = true;
        initiatedateinputevent(node, 'fromdate');
        initiatedateinputevent(node, 'todate');
        initiateengagementevent(node);
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
    const fromdateEnableElement = node.one('input[name=fromdate_enabled]');
    const fromdateDateElement = node.one('input[name=fromdate_date]');
    const fromdateTimeElement = node.one('input[name=fromdate_time]');
    const todateEnableElement = node.one('input[name=todate_enabled]');
    const todateDateElement = node.one('input[name=todate_date]');
    const todateTimeElement = node.one('input[name=todate_time]');
    const engagementinternational = node.one('input[name=engagementinternational]');

    value.forum = forumElement?.get('value') ?? null;
    value.metric = metricElement?.get('value') ?? null;
    value.condition = conditionElement?.get('value') ?? null;
    value.value = valueElement?.get('value') ?? null;
    value.fromdate = {
        enabled: (fromdateEnableElement?.get('checked') ?? false) ? true : false,
        date: fromdateDateElement?.get('value') ?? null,
        time: fromdateTimeElement?.get('value') ?? null
    };
    value.todate = {
        enabled: (todateEnableElement?.get('checked') ?? false) ? true : false,
        date: todateDateElement?.get('value') ?? null,
        time: todateTimeElement?.get('value') ?? null
    };
    value.engagementinternational = engagementinternational?.get('checked') ?? false;
};

M.availability_forummetric.form.fillErrors = function(errors, node) {};


}, '@VERSION@', {"requires": ["base", "node", "moddle-core_availability-form"]});
