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
            var hiddenSection = $('section.hidden');
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
            var hiddenSection = $('section.hidden');
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
    });
