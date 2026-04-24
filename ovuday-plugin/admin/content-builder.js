/* OvuDay Content Builder — admin JS */
(function($){
'use strict';

/* ── Icon picker ───────────────────────────────────── */
$(document).on('click', '.cb-icon-btn', function(){
    var $btn  = $(this);
    var $wrap = $btn.closest('.cb-row');
    $wrap.find('.cb-icon-btn').removeClass('selected');
    $btn.addClass('selected');
    $wrap.find('.icon-value').val($btn.data('icon'));
});

/* ── Remove repeater item ──────────────────────────── */
$(document).on('click', '.remove-item', function(){
    $(this).closest('.cb-repeater-item').remove();
    reindex();
});

/* ── Add repeater item ─────────────────────────────── */
$(document).on('click', '.cb-add-item', function(){
    var type = $(this).data('repeater');

    if (type === 'trust')    { addTrust();    return; }
    if (type === 'stats')    { addStat();     return; }
    if (type === 'features') { addFeature();  return; }
    if (type === 'steps')    { addStep();     return; }
    if (type === 'faq')      { addFaq();      return; }
    if (type === 'footer-col') { addFooterCol(); return; }
    if (type && type.startsWith('footer-link-')) {
        var ci = type.replace('footer-link-','');
        addFooterLink(ci);
        return;
    }
});

/* ── Reindex: renumber all repeater [n] indices ───── */
function reindex(){
    ['#trust-repeater','#stats-repeater','#features-repeater','#steps-repeater','#faq-repeater'].forEach(function(sel){
        $(sel).children('.cb-repeater-item').each(function(i){
            $(this).find('[name]').each(function(){
                var n = $(this).attr('name');
                $(this).attr('name', n.replace(/\[\d+\]/, '['+i+']'));
            });
        });
    });
}

/* ── Templates ─────────────────────────────────────── */
var ICONS = ['ShieldCheck','Lock','Zap','Heart','Star','CalendarDays','Gift','Clock','Calendar',
             'Check','Circle','Globe','Moon','Sun','Leaf','Flower','Sparkles','Award',
             'Activity','BookOpen','Brain','Eye','Home','Info','Lightbulb','Mail',
             'Target','TrendingUp','User','Users','Video','ArrowRight'];

function iconGrid(name, selected){
    var html = '<div class="cb-row"><label>Icon</label>';
    html += '<input type="hidden" name="'+name+'" value="'+selected+'" class="icon-value">';
    html += '<div class="cb-icon-grid">';
    ICONS.forEach(function(ic){
        html += '<button type="button" class="cb-icon-btn'+(ic===selected?' selected':'')+'" data-icon="'+ic+'">'+ic+'</button>';
    });
    html += '</div></div>';
    return html;
}

function colorField(name, val){
    return '<div class="cb-row"><label>Accent Color</label><div class="cb-color-row">'
        +'<input type="color" name="'+name+'" value="'+val+'">'
        +'<input type="text" name="'+name+'" value="'+val+'">'
        +'</div></div>';
}

function addTrust(){
    var i = $('#trust-repeater .cb-repeater-item').length;
    var html = '<div class="cb-repeater-item">'
        +'<button type="button" class="remove-item">Remove</button>'
        +'<div class="cb-grid-3">'
        +iconGrid('trust[items]['+i+'][icon]','ShieldCheck')
        +'<div class="cb-row"><label>Badge Text</label><input type="text" name="trust[items]['+i+'][text]" value=""></div>'
        +colorField('trust[items]['+i+'][color]','#10b981')
        +'</div></div>';
    $('#trust-repeater').append(html);
}

function addStat(){
    var i = $('#stats-repeater .cb-repeater-item').length;
    var html = '<div class="cb-repeater-item">'
        +'<button type="button" class="remove-item">Remove</button>'
        +'<div class="cb-grid-3">'
        +'<div class="cb-row"><label>Value</label><input type="text" name="stats[items]['+i+'][value]" value=""></div>'
        +'<div class="cb-row"><label>Suffix</label><input type="text" name="stats[items]['+i+'][suffix]" value=""></div>'
        +'<div class="cb-row"><label>Label</label><input type="text" name="stats[items]['+i+'][label]" value=""></div>'
        +'</div></div>';
    $('#stats-repeater').append(html);
}

function addFeature(){
    var i = $('#features-repeater .cb-repeater-item').length;
    var html = '<div class="cb-repeater-item">'
        +'<button type="button" class="remove-item">Remove</button>'
        +'<div class="cb-grid-2">'
        +iconGrid('features[items]['+i+'][icon]','Star')
        +colorField('features[items]['+i+'][color]','#E8476E')
        +'<div class="cb-row"><label>Title</label><input type="text" name="features[items]['+i+'][title]" value=""></div>'
        +'<div class="cb-row"><label>Description</label><textarea name="features[items]['+i+'][description]"></textarea></div>'
        +'</div></div>';
    $('#features-repeater').append(html);
}

function addStep(){
    var i = $('#steps-repeater .cb-repeater-item').length;
    var html = '<div class="cb-repeater-item">'
        +'<button type="button" class="remove-item">Remove</button>'
        +'<div class="cb-grid-2">'
        +'<div class="cb-row"><label>Step Number / Label</label><input type="text" name="steps[items]['+i+'][number]" value="'+(i+1)+'"></div>'
        +iconGrid('steps[items]['+i+'][icon]','Circle')
        +'<div class="cb-row"><label>Title</label><input type="text" name="steps[items]['+i+'][title]" value=""></div>'
        +'<div class="cb-row"><label>Description</label><textarea name="steps[items]['+i+'][description]"></textarea></div>'
        +'</div></div>';
    $('#steps-repeater').append(html);
}

function addFaq(){
    var i = $('#faq-repeater .cb-repeater-item').length;
    var html = '<div class="cb-repeater-item">'
        +'<button type="button" class="remove-item">Remove</button>'
        +'<div class="cb-row"><label>Question</label><input type="text" name="faq[items]['+i+'][question]" value=""></div>'
        +'<div class="cb-row"><label>Answer (HTML ok)</label><textarea name="faq[items]['+i+'][answer]"></textarea></div>'
        +'<div class="cb-row"><label>Category</label><input type="text" name="faq[items]['+i+'][category]" value="general" placeholder="general, fertility, privacy…"></div>'
        +'</div>';
    $('#faq-repeater').append(html);
}

function addFooterCol(){
    var i = $('#footer-col-repeater .cb-repeater-item').length;
    var html = '<div class="cb-repeater-item">'
        +'<button type="button" class="remove-item">Remove Column</button>'
        +'<div class="cb-row"><label>Column Heading</label><input type="text" name="footer[links]['+i+'][title]" value=""></div>'
        +'<div id="footer-link-repeater-'+i+'"></div>'
        +'<button type="button" class="cb-add-item" style="margin-top:8px;" data-repeater="footer-link-'+i+'">+ Add Link</button>'
        +'</div>';
    $('#footer-col-repeater').append(html);
}

function addFooterLink(ci){
    var li = $('#footer-link-repeater-'+ci+' .cb-repeater-item').length;
    var html = '<div class="cb-repeater-item" style="background:#fff;">'
        +'<button type="button" class="remove-item">✕</button>'
        +'<div class="cb-grid-2">'
        +'<div class="cb-row"><label>Link Label</label><input type="text" name="footer[links]['+ci+'][items]['+li+'][label]" value=""></div>'
        +'<div class="cb-row"><label>Link URL</label><input type="url" name="footer[links]['+ci+'][items]['+li+'][url]" value="https://"></div>'
        +'</div></div>';
    $('#footer-link-repeater-'+ci).append(html);
}

/* ── Sync color text ↔ color picker ───────────────── */
$(document).on('input', 'input[type=color]', function(){
    $(this).next('input[type=text]').val($(this).val());
});
$(document).on('input', '.cb-color-row input[type=text]', function(){
    var v = $(this).val();
    if(/^#[0-9a-fA-F]{6}$/.test(v)) $(this).prev('input[type=color]').val(v);
});

})(jQuery);
