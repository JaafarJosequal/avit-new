define([
        "jquery",
        "unveil",
        "mage/cookies",
        "fancybox",
        "owlcarousel",
        "domReady!"
    ],
    function ($) {
        /**
         * Lazy load image
         */

        if ($('.enable-ladyloading').length) {
            function _runLazyLoad() {
                $("img.lazyload").unveil(0, function () {
                    $(this).on('load', function(){
                        this.classList.remove("lazyload");
                    });
                });
            }

            setTimeout(function () {
                _runLazyLoad();
            }, 1000);

            $(document).on("afterAjaxLazyLoad", function () {
                _runLazyLoad();
            });
        }

        /**
         * Back to top
         */
        $(function () {
            $(window).scroll(function () {
                if ($(this).scrollTop() > 500) {
                    $('.back2top').addClass('active');
                } else {
                    $('.back2top').removeClass('active');
                }
            });
            $('.back2top').click(function () {
                $('body,html').animate({
                    scrollTop: 0
                }, 800);
                return false;
            });

        });

        /**
         * Newsletter popup
         */
        if ($('.enable-newsletter-popup').length && $('#newsletter-popup').length) {
            var check_cookie = jQuery.cookie('newsletter_popup');
            if (check_cookie == null || check_cookie == 'shown') {
               setTimeout(function () {
                popupNewsletter(); 
				console.log('11');
            }, 30000);
            }
            jQuery('#newsletter-popup .subscribe-bottom input').on('click', function () {
                if (jQuery(this).parent().find('input:checked').length) {
                    var check_cookie = jQuery.cookie('newsletter_popup');
                    if (check_cookie == null || check_cookie == 'shown') {
                        jQuery.cookie('newsletter_popup', 'dontshowitagain');
                    } else {
                        jQuery.cookie('newsletter_popup', 'shown');
                        popupNewsletter();
                    }
                } else {
                    jQuery.cookie('newsletter_popup', 'shown');
                }
            });

            function popupNewsletter() {
                $.fancybox.open('#newsletter-popup');
            }
			
        }

        /**
         * Owl slider init
         */

        var owl_data = $('div[data-owl="owl-slider"]');
        owl_data.each(function () {
            var dataOwl = $(this);
            var dotsMobile = dataOwl.data('mobile-dots') == undefined ? false : dataOwl.data('mobile-dots');
			var marginMobile = dataOwl.data('mobile-margin') == undefined ? 30 : dataOwl.data('mobile-margin');
			var navMobile = dataOwl.data('mobile-nav') == undefined ? false : dataOwl.data('mobile-nav');
            if (dotsMobile || marginMobile) {
                dataOwl.find('.owl-carousel').owlCarousel({
                    autoplay: dataOwl.data('autoplay') == undefined ? false : dataOwl.data('autoplay'),
                    autoplayHoverPause: dataOwl.data('autoplayhoverpause') == undefined ? false : dataOwl.data('autoplayhoverpause'),
                    loop: dataOwl.data('loop') == undefined ? false : dataOwl.data('loop'),
                    center: dataOwl.data('center') == undefined ? false : dataOwl.data('center'),
                    margin: dataOwl.data('margin') == undefined ? 0 : dataOwl.data('margin'),
                    stagePadding: dataOwl.data('stagepadding') == undefined ? 0 : dataOwl.data('stagepadding'),
                    nav: dataOwl.data('nav') == undefined ? false : dataOwl.data('nav'),
                    dots: dataOwl.data('dots') == undefined ? false : dataOwl.data('dots'),
                    mouseDrag: dataOwl.data('mousedrag') == undefined ? false : dataOwl.data('mousedrag'),
                    touchDrag: dataOwl.data('touchdrag') == undefined ? false : dataOwl.data('touchdrag'),

                    responsive: {
                         0: {
                            items: dataOwl.data('screen0') == undefined ? 1 : dataOwl.data('screen0'),
							margin:dataOwl.data('mobile-margin') == undefined ? 30 : dataOwl.data('mobile-margin'), 
                            nav: dataOwl.data('mobile-nav') == undefined ? false : dataOwl.data('mobile-nav'),
                            dots: dotsMobile = dataOwl.data('mobile-dots') == undefined ? false : dataOwl.data('mobile-dots')
                        },
                        481: {
                            items: dataOwl.data('screen481') == undefined ? 1 : dataOwl.data('screen481'),
							margin:dataOwl.data('mobile-margin') == undefined ? 30 : dataOwl.data('mobile-margin'),
                            nav: dataOwl.data('mobile-nav') == undefined ? false : dataOwl.data('mobile-nav'),
                            dots: dotsMobile = dataOwl.data('mobile-dots') == undefined ? false : dataOwl.data('mobile-dots')
                        },
						768: {
                            items: dataOwl.data('screen768') == undefined ? 1 : dataOwl.data('screen768'),
							margin:dataOwl.data('mobile-margin') == undefined ? 30 : dataOwl.data('mobile-margin'),
                            nav:dataOwl.data('mobile-nav') == undefined ? false : dataOwl.data('mobile-nav'),
                            dots: dotsMobile = dataOwl.data('mobile-dots') == undefined ? false : dataOwl.data('mobile-dots')
                        },
						
                        991: {
                            items: dataOwl.data('screen768') == undefined ? 1 : dataOwl.data('screen768'),
							margin:dataOwl.data('mobile-margin') == undefined ? 30 : dataOwl.data('mobile-margin'),
                            nav:dataOwl.data('mobile-nav') == undefined ? false : dataOwl.data('mobile-nav'),
                            dots: dotsMobile = dataOwl.data('mobile-dots') == undefined ? false : dataOwl.data('mobile-dots')
                        },
						
                        992: {
                            items: dataOwl.data('screen992') == undefined ? 1 : dataOwl.data('screen992')
                        },
                        1200: {
                            items: dataOwl.data('screen1200') == undefined ? 1 : dataOwl.data('screen1200')
                        },
                        1441: {
                            items: dataOwl.data('screen1441') == undefined ? 1 : dataOwl.data('screen1441')
                        },
                        1681: {
                            items: dataOwl.data('screen1681') == undefined ? 1 : dataOwl.data('screen1681')
                        },
                        1920: {
                            items: dataOwl.data('screen1920') == undefined ? 1 : dataOwl.data('screen1920')
                        },
                    }
                })
            } else {
                dataOwl.find('.owl-carousel').owlCarousel({
                    autoplay: dataOwl.data('autoplay') == undefined ? false : dataOwl.data('autoplay'),
                    autoplayHoverPause: dataOwl.data('autoplayhoverpause') == undefined ? false : dataOwl.data('autoplayhoverpause'),
                    loop: dataOwl.data('loop') == undefined ? false : dataOwl.data('loop'),
                    center: dataOwl.data('center') == undefined ? false : dataOwl.data('center'),
                    margin: dataOwl.data('margin') == undefined ? 0 : dataOwl.data('margin'),
                    stagePadding: dataOwl.data('stagepadding') == undefined ? 0 : dataOwl.data('stagepadding'),
                    nav: dataOwl.data('nav') == undefined ? false : dataOwl.data('nav'),
                    dots: dataOwl.data('dots') == undefined ? false : dataOwl.data('dots'),
                    mouseDrag: dataOwl.data('mousedrag') == undefined ? false : dataOwl.data('mousedrag'),
                    touchDrag: dataOwl.data('touchdrag') == undefined ? false : dataOwl.data('touchdrag'),

                    responsive: {
                        0: {
                            items: dataOwl.data('screen0') == undefined ? 1 : dataOwl.data('screen0')
                        },
                        481: {
                            items: dataOwl.data('screen481') == undefined ? 1 : dataOwl.data('screen481')
                        },
                        768: {
                            items: dataOwl.data('screen768') == undefined ? 1 : dataOwl.data('screen768')
                        },
                        992: {
                            items: dataOwl.data('screen992') == undefined ? 1 : dataOwl.data('screen992')
                        },
                        1200: {
                            items: dataOwl.data('screen1200') == undefined ? 1 : dataOwl.data('screen1200')
                        },
                        1441: {
                            items: dataOwl.data('screen1441') == undefined ? 1 : dataOwl.data('screen1441')
                        },
                        1681: {
                            items: dataOwl.data('screen1681') == undefined ? 1 : dataOwl.data('screen1681')
                        },
                        1920: {
                            items: dataOwl.data('screen1920') == undefined ? 1 : dataOwl.data('screen1920')
                        },
                    }
                })
            }


        });

        function menu_full(){
            $window_width = $( window ).width();
            $('.sm_megamenu_dropdown_6columns').innerWidth($window_width);
            $('.sm_megamenu_dropdown_6columns').offset({ left: 0 });
        }

        $( window ).on( "load", function() {
			if ($(window).width() > 1050) {
				menu_full();
			}
        });   
        
        var rtime;
        var timeout = false;
        var delta = 200;
        $(window).resize(function() {
            rtime = new Date();
            if (timeout === false) {
                timeout = true;
                setTimeout(resizeend, delta);
            }
        });

        function resizeend() {
            if (new Date() - rtime < delta) {
                setTimeout(resizeend, delta);
            } else {
                timeout = false;
				if ($(window).width() > 1050) {
					menu_full();
				}
            }               
        }
        
        /* Auto click product's attributes */
        function activeProAttr(product_items){
            product_items.each(function(){
                var obj = $(this);
                var attributes = obj.find('.swatch-attribute');
                attributes.each(function(){
                    var attr = $(this);
                    var first_attr = attr.find('.swatch-option').eq(0);
                    first_attr.trigger('click');
                })
            });
        }
            
        window.activeProAttr = activeProAttr;
        
        function checkAllOptionsSelected(attributes, option_id, current_select){
            var attr_count = attributes.length;
            var count_selected = 0;
            
            attributes.each(function(){
                var attr = $(this);
                var p_options = attr.find('.swatch-option');
                p_options.each(function(){
                    var option_child = $(this);
                    if(option_child.data('option-id') == option_id){
                        if(current_select){
                            console.log(option_id);
                            count_selected++;
                            return false;
                        }
                    } else {
                        if(option_child.hasClass('selected')){
                            count_selected++;
                            return false;
                        }
                    }

                })
            });
            
            if(count_selected == attr_count){
                return true;
            }
            return false;
        }

        $(document).ready(function() {
            var product_items = $('.product-item');    
            var count_check_attribute = 0;
            const checkAttrInterval = setInterval(checkAttrLoaded, 1000, product_items);
            
            function checkAttrLoaded(product_items) {
                count_check_attribute++;

                product_items.each(function(){
                    var obj = $(this);
                    var attributes = obj.find('.swatch-attribute');
                    if(attributes.length>0){
                        clearInterval(checkAttrInterval);
                        window.activeProAttr(product_items);
                        return false;
                    }
                });

                if(count_check_attribute>=10){
                    clearInterval(checkAttrInterval);
                }
            }
            
            /* Check if product page loaded - select first attributes */
            if( $('body').hasClass('catalog-product-view') ) {
                setTimeout(function() {
                    var attributes = $('#product_addtocart_form').find('.swatch-attribute');
                    if( attributes.length>0 ) {
                        $("#product-addtocart-button").css("pointer-events", "none");
                        $("#product-addtocart-button > .button-enable").hide();
                        $("#product-addtocart-button > .button-disable").show();
                        attributes.each(function(){
                            var attr = $(this);
                            var options = attr.find('.swatch-option');
                            options.each(function(){
                                var p_option = $(this);
                                p_option.on('click',function(){
                                    //console.log(p_option.data('option-id'));
                                    
                                    if(p_option.hasClass('selected')){
                                        var current_select = false;
                                    } else {
                                        var current_select = true;
                                    }
                                    
                                    var option_id = p_option.data('option-id');
                                    options_selected = checkAllOptionsSelected(attributes, option_id, current_select);
                                    if(options_selected){
                                        $("#product-addtocart-button").css("pointer-events", "auto");
                                        $("#product-addtocart-button > .button-enable").show();
                                        $("#product-addtocart-button > .button-disable").hide();
                                    } else {
                                        $("#product-addtocart-button").css("pointer-events", "none");
                                        $("#product-addtocart-button > .button-enable").hide();
                                        $("#product-addtocart-button > .button-disable").show();
                                    }

                                });
                            });
                            //var first_attr = attr.find('.swatch-option').eq(0);
                            //first_attr.trigger('click');
                        });
                    }
                }, 1500); 
               
            }
        });
        
        $(window).on('updateAutoClickAttributes', function () {
            setTimeout(function() {
                var product_items = $('.products.list.items.product-items .product-item');  
                window.activeProAttr(product_items);
            }, 1000); 
        });
            
    }
);

