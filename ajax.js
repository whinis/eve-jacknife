    function getCharacterInfo(cID,uID,vCode){
        var array={action:"character", cID:cID, uID:uID,vCode:vCode};
        $.ajax({
            type: 'POST',
            url: 'ajax.php?t='+new Date().getTime(),
            data: array,
            async: true,
            success: function(data,textStatus,XHR){
                returnValue = $.parseJSON(data);
                if (returnValue.result == "success") {
                    $("#isk"+returnValue.id).html(returnValue.balance);
                    $("#sp"+returnValue.id).html(returnValue.sptotal);
                    $("#bday"+returnValue.id).html(returnValue.dob);
                    if($("#tIsk").html()!="")
                        totalIsk=parseFloat($("#tIsk").html().replace(/,/g,""));
                    else
                        totalIsk=0;
                    addIsk=parseFloat(returnValue.balance.replace(/,/g,""));
                    totalIsk+=addIsk;
                    $("#tIsk").html(numberWithCommas(totalIsk));

                    if($("#tSp").html()!="")
                        totalSp=parseFloat($("#tSp").html().replace(/,/g,""));
                    else
                        totalSp=0;
                    addSp=parseFloat(returnValue.sptotal.replace(/,/g,""));
                    totalSp+=addSp;
                    $("#tSp").html(numberWithCommas(totalSp));
                }else{
                    $("#infobar").after("<div data-alert class='alert-box alert'>Failed to load character data<a href='#' class='close'>&times;</a></div>");
                }
            }
        });
    }
    function numberWithCommas(x) {
        x= x.toFixed(2);
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    $(document).ready(function(){
        $("#redFlag").click(function(e){
            var hiddenSection = $('#redFlagBox').parents('section.hidden');
            hiddenSection.fadeIn()
                // unhide section.hidden
                .css({ 'display':'block' })
                // set to full screen
                .css({ width: $(window).width() + 'px', height: $(window).height() + 'px' })
                .css({ top:($(window).height() - hiddenSection.height())/2 + 'px',
                    left:($(window).width() - hiddenSection.width())/2 + 'px' })
                // greyed out background
                .css({ 'background-color': 'rgba(0,0,0,0.5)' })
                .appendTo('body');
            // console.log($(window).width() + ' - ' + $(window).height());
            $('span.close').click(function(){ $(hiddenSection).fadeOut(); });
        });
        $("#evePraisal").click(function(e){
            var hiddenSection = $('#evePraisalBox').parents('section.hidden');
            hiddenSection.fadeIn()
                // unhide section.hidden
                .css({ 'display':'block' })
                // set to full screen
                .css({ width: $(window).width() + 'px', height: $(window).height() + 'px' })
                .css({ top:($(window).height() - hiddenSection.height())/2 + 'px',
                    left:($(window).width() - hiddenSection.width())/2 + 'px' })
                // greyed out background
                .css({ 'background-color': 'rgba(0,0,0,0.5)' })
                .appendTo('body');
            // console.log($(window).width() + ' - ' + $(window).height());
            $('span.close').click(function(){ $(hiddenSection).fadeOut(); });
        });
        $("#saveRedFlag").click(function(e){
            var hiddenSection = $('#redFlagBox').parents('section.hidden');
            var array={action:"redFlag", characters:$("#redFlagBox").val()};
            $.ajax({
                type: 'POST',
                url: 'ajax.php?t=' + new Date().getTime(),
                data: array,
                async: true,
                success: function (data, textStatus, XHR) {
                    $(hiddenSection).fadeOut();
                    location.reload();
                }
            });
        });
        $("#changeMessageFormatting").click(function(e){
            var val=true;
            if($(this).val()=="Formatted"){
                val=false;
            }
            var array={action:"changeMailFormat",val:val};
            $.ajax({
                type: 'POST',
                url: 'ajax.php?t=' + new Date().getTime(),
                data: array,
                context: $(this),
                async: true,
                success: function (data, textStatus, XHR) {
                    if($(this).val()=="Formatted"){
                        $(this).val("Unformatted");
                    }else{
                        $(this).val("Formatted");
                    }
                }
            });
        });
        $(".messageRow").click(function(e) {
            if(!$(this).next("tr").hasClass("messageBody")){
                $(".messageBody").remove();
                $.ajax({
                    type: 'GET',
                    url: $(this).attr("href")+"&ajax=true",
                    context: $(this),
                    async: true,
                    success: function (data, textStatus, XHR) {
                        data= $.parseJSON(data);
                        if(data.result=="success"){
                            $(this).after("<tr class=\"messageBody\"><td colspan='4'>"+data.body+"</td></tr>" );
                        }
                    },
                    error:function (data, textStatus, XHR) {
                        location.href=$(this).attr("href");
                    },
                    fail: function (data, textStatus, XHR) {
                        location.href=$(this).attr("href");
                    }
                });
            }else {
                $(".messageBody").remove();
            }
        });
        $(".editKey").click(function(e) {
            e.preventDefault();
            $("#keyInfoBox").show();
            $("#saveKey").attr("keyID",$(this).attr("keyID"));
            $(".fadeDiv").show();
            $("#keyName").val($(this).parent().parent().find(".keyName").text());
            $("#notes").val($(this).parent().parent().find(".keyNotes").text());
            $("#keyName").attr("changed",false);
            $("#notes").attr("changed",false);
        });
        $(".fadeDiv").click(function(e){
            $(".floating_login_div").hide();
            $(".fadeDiv").hide();
        })
        $(".exitbutton").click(function(e){
            $(this).parent().hide();
            $(".fadeDiv").hide();
        })
        $("#saveKey").click(function(e){
            $(".floating_login_div").hide();
            $(".fadeDiv").hide();
            var id=$(this).attr("keyID");
            var array={
                action:"editKey",
                keyID:id,
                name:false,
                notes:false
            };
            if($("#keyName").attr("changed")=="true"){
                array['name']=$("#keyName").val();
            }
            if($("#notes").attr("changed")=="true"){
                array['notes']=$("#notes").val();
            }
            $.ajax({
                type: 'POST',
                url: 'ajax.php?t=' + new Date().getTime(),
                data: array,
                context:$(this),
                async: true,
                success: function (data, textStatus, XHR) {
                    data= $.parseJSON(data);
                    var id=$(this).attr("keyID");
                    if(data.result=="success") {
                        if($("#keyName").attr("changed")=="true") {
                            $("tr[keyID='"+id+"']").find(".keyName").text($("#keyName").val());
                            $("#keyName").val("")
                            $("#keyName").attr("changed",false);
                        }
                        if($("#notes").attr("changed")=="true") {
                            $("tr[keyID='"+id+"']").find(".keyNotes").text($("#notes").val());
                            $("#notes").val("")
                            $("#notes").attr("changed",false);
                        }
                    }else{
                        alert("Error Editting Key");
                    }
                }
            });
        });
        $("#keyName").change(function(){
            $("#keyName").attr("changed",true);
        });
        $("#notes").change(function(){
            $("#notes").attr("changed",true);
        });
        $("body").on("click",".removeKey",function(e){
            e.preventDefault();
            var id=$(this).attr("keyID");
            var array={action:"removeKey", keyID:id};
            $.ajax({
                type: 'POST',
                url: 'ajax.php?t=' + new Date().getTime(),
                data: array,
                context: $(this),
                async: true,
                success: function (data, textStatus, XHR) {
                    data= $.parseJSON(data);
                    if(data.result=="success") {
                        if($(this).parent().parent().is("tr")) {
                            $(this).parent().parent().remove();
                        }else{
                            alert("Key Removed");
                            $(this).remove();
                        }
                    }else{
                        alert("Error Removing Key");
                    }
                }
            });
        });
    });
