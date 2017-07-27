function query_regenerate_img(e) {
    $("input[name='image_regenerator_queue_what']").val(e), image_regenerator_can_run_queue ? $.ajax({
        type: "POST",
        url: autoImgPath,
        data: {image:autoImg[e].todo[0],type: e,ajax:true,action:"RegenerateMethod",watermark:($('#image_regenerator-watermark').is(":checked"))? 1 : 0},
        dataType: "json",
        success: function(a) {
            1 == a.success ? ($("#autoImg-progress").prepend(["<p>"+ir_success_str+" : ", e, " ", autoImg[e].todo[0], "</p>"].join("")), autoImg[e].done.push(autoImg[e].todo[0]), autoImg[e].todo.shift()) : ($("#autoImg-progress").prepend(["<p>"+ir_error_str+" : ", e, " ", autoImg[e].todo[0], "</p>"].join("")), autoImg[e].errors.push(autoImg[e].todo[0]), autoImg[e].todo.shift()), $(["#image_regenerator-infos-", e].join("")).find(".image_regenerator-done").html(autoImg[e].done.length), autoImg[e].todo.length > 0 ? (query_regenerate_img(e), progress = Math.round(100 * (autoImg[e].done.length / (autoImg[e].done.length + autoImg[e].todo.length)), 2), $(["#image_regenerator-infos-", e].join("")).find(".progress-bar").attr("aria-valuenow", progress), $(["#image_regenerator-infos-", e].join("")).find(".progress-bar").css("width", [progress, "%"].join(""))) : ($("#autoImg-progress").prepend(["<p>"+ir_finished_str+" : ", e, "</p>"].join("")), $(".image_regenerator-lauch").removeAttr("disabled"), $("#image_regenerator-save").removeAttr("disabled"), $(["#image_regenerator-infos-", e].join("")).find(".progress-bar").css("width", "100%"))
        }
    }) : image_regenerator_queuing_what = e
}
jQuery(document).ready(function(e) {
    var a = 0;
    e.each(autoImg, function(r, t) {
        a = Math.round(100 * (t.done.length / (t.todo.length + t.done.length)), 2), e("#autoImg-buttons").append(["<tr id='image_regenerator-infos-", r, "'><td><button class='image_regenerator-lauch btn btn-info' data-what='", r, "'><span class='icon-play'></span> "+ir_regenerate_str+ " ", r, "</button></td><td><span class='image_regenerator-done'>", t.done.length, "</span>/<span class='image_regenerator-remaining-norefresh'>", t.done.length + t.todo.length, "</span></td><td width='50%'><div class='progress'  style='margin-bottom:0'><div class='progress-bar' role='progressbar' aria-valuenow='", a, "' aria-valuemin='0' aria-valuemax='100' style='width: ", a, "%;'><span class='sr-only'>", a, "% Complete</span></div></div></td></tr>"].join(""))
    }), e(".image_regenerator-lauch").live("click", function() {
        var a = e(this).attr("data-what");
        e(".image_regenerator-lauch").attr("disabled", "disabled"), e("#image_regenerator-save").attr("disabled", "disabled"), query_regenerate_img(a)
    }), e("#image_regenerator-pause").click(function() {
        image_regenerator_can_run_queue = !1, e("#image_regenerator-resume").removeAttr("disabled"), e("#image_regenerator-save").removeAttr("disabled"), e(this).attr("disabled", "disabled"), e("input[name='image_regenerator_queue']").val(JSON.stringify(autoImg)), e("#image_regenerator_save_form").submit()
    }), e("#image_regenerator-resume").click(function() {
        image_regenerator_can_run_queue = !0, e("#image_regenerator-pause").removeAttr("disabled"), e(this).attr("disabled", "disabled"), e("#image_regenerator-save").attr("disabled", "disabled"), query_regenerate_img(image_regenerator_queuing_what)
    }), e("#image_regenerator-save").click(function() {
        e("input[name='image_regenerator_queue']").val(JSON.stringify(autoImg)), e("#image_regenerator_save_form").submit()
    })
});
