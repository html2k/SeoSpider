jQuery(document).ready(function($){
  $('input[type=submit],input[type=checkbox]').button();
  //$('input[type=checkbox]').button()

  $('form#SeoSpider').submit(function(){
    require(true,$('input#url').val(),$('input#internal_pages').is(':checked'),$('input#sub_domains').is(':checked'));
    return false;
  });

  $( "#slider" ).slider({min:0, max:100, step:10,
    slide: function( event, ui ) {
      $( "a.ui-slider-handle" ).text(ui.value+'%');
    },
    change: function(){
      var sliderVal = parseInt($("#slider").slider("value"),10);
      $('div[data-percent]').each(function() {
        if(parseInt($(this).data().percent,10) < sliderVal)
          $(this).hide();
        else
          $(this).show();
      })
    }});
  $( "a.ui-slider-handle" ).text('0%');

  jQuery(document).on('click', 'h3', function(){
    var h3 = jQuery(this);
    var ul = h3.parent('div').find('ul');
    if(h3.hasClass('ui-corner-all')){ //è chiuso quindi lo apro
      h3.removeClass('ui-corner-all').addClass('ui-corner-top ui-accordion-header-active ui-state-active');
      ul.show();
    }else{
      h3.removeClass('ui-corner-top ui-accordion-header-active ui-state-active').addClass('ui-corner-all');
      ul.hide();
    }
  })
});

function require(restart,urls,internal_pages,sub_domains){
  var links = '';
  $.ajax("SeoSpider.php",{
    type: "POST",
    data:{"url": urls, 'restart' : restart, 'internal_pages': internal_pages, 'sub_domains': sub_domains},
    dataType: 'json',
    success: function (data){
      jQuery.each(data, function(i, val) {
        if(i == 'counter') {
          jQuery('#total').text(val);
          return;
        }
        /*
        if($('#accordion > div').length > 1){
          $('#robots').show();
          if(i.search('robots.txt') > -1){
            $('#robots ul').html('<h3 class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all ui-accordion-icons">ROBOTS.TXT<div class="bar"></div></h3><ul class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active"><li>OK</li></ul>');
            return;
          }
        }
        */
        var text = '<div><h3 class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all ui-accordion-icons">'+i+'<div class="bar"></div></h3><ul class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active">';

        if(typeof(val.status) != 'undefined'){
          var status_code = val.status.match(/HTTP.* ([\d]+) .*$/i);

          text +=
            '<li><a href="'+i+'">go to the page</a></li>'+

              '<li class="'+((status_code != null && status_code[0] != 200) || val.redirect_url.length > 0 ? 'ui-state-error' : '')+'"><strong>Status:</strong> '+val.status+
          (val.redirect_url.length > 0 ? '<strong>Redirected to url:</strong> '+val.redirect_url : '') +'</li>'+

          '<li class="'+(val.content.length == 0 ? 'ui-state-error' : '')+'"><strong>Content type:</strong> '+val.content+'</li>'+

          '<li class="'+(val.title_lenght < 10 || val.title_lenght > 70  || val.title_items > 1 ? 'ui-state-error' : '')+'"><strong>Title:</strong> '+val.title+
          (val.title_items > 1 ? '<strong>More than one title in the same page!</strong>': '') + '</li>'+

          '<li class="description '+(val.meta_description_lenght < 70 || val.meta_description_lenght > 160 || val.meta_description_items > 1 ? 'ui-state-error' : '')+'"><strong>Description:</strong> '+val.meta_description+
          (val.meta_description_items > 1 ? '<strong>More than one meta description in the same page!</strong>' : '') + '</li>'+

          '<li class="keywords '+(val.meta_keywords_lenght < 5 || val.meta_keywords_lenght > 255 || val.meta_keywords_items > 1 ? 'ui-state-error' : '')+'"><strong>Keywords:</strong> '+val.meta_keywords+
          (val.meta_keywords_items > 1 ? '<strong>More than one meta keywords in the same page!</strong>' : '') + '</li>'+

          '<li class="h1 '+(val.h1_lenght < 5 || val.h1_lenght > 255 || val.h1_items > 1 ? 'ui-state-error' : '')+'"><strong>H1:</strong> '+val.h1+
          (val.h1_items > 1 ? '<strong>More than one h1 in the same page!</strong>' : '') + '</li>'+

          '<li class="h2 '+(val.h2_lenght < 5 || val.h2_lenght > 255 || val.h2_items == 0? 'ui-state-error' : '')+'"><strong>H2:</strong> '+val.h2+'</li>'+

          (val.images_without_alt > 0 ? '<li class="images ui-state-error"><strong>Images without ALT tag:</strong> '+val.images_without_alt+'</li>' : '')+

          (val.outlinks_without_title > 0 ? '<li class="links ui-state-error"><strong>Links without TITLE tag:</strong> '+val.outlinks_without_title+'</li>' : '');
        }
        else{
          text += '<li class="ui-state-error"><strong>Status:</strong> 404</li>';
        }
        text += '</ul></div>';
        $('#accordion').append(text);
        var bar_width = ($('#accordion div:last-child ul li.ui-state-error').length / $('#accordion div:last-child ul li').length) * 100;
        $('#accordion div:last-child .bar').css('width',bar_width*0.9+'%');
        $('#accordion div:last-child h3').prepend('('+Math.round(bar_width)+'%) ');
        $('#accordion div:last-child').attr('data-percent',Math.round(bar_width));
      });
      jQuery('#partial').text(jQuery('#accordion > div').length);
    }
  })
  .done(function() {
    if($('input#internal_pages').is(':checked') == true)
      require(false,null,true,$('input#sub_domains').is(':checked'));
  });
}