define([
        "jquery",
        "unveil",
        "owlcarousel",
        "slick",
		"marquee",
		'jquery.nav',
        "domReady!"
    ],
    function ($) {
        /**
         * Theme custom javascript
         */

        /**
         * Fix hover on IOS
         */
        $('body').bind('touchstart', function () {
        });

		/**
         * Click button close header
         */
		$('.header-top .icon-x').click(function () {
             $('.header-top').slideUp();
        });

        $('.header-top .topbar-close').click(function () {
             $('.header-top').slideUp();
        });
		
       
		/**
         * Click box search
         */
        $('.search-header .btn-search, .mysearchtop-drop .button-sticky').click(function () {
           $('body').addClass('search-ajax-active'); 
		   $('.header-content .customer-actions .search-container .popup-search .input-text').focus();  
        });
		$("body").on('click', '.close-search, .search-overlay', function () {
            $('body').removeClass('search-ajax-active');
			 
			$('.sticky-bottom .content-group-block .group-block .block-bottom').removeClass('active');
			$("html").removeClass("overflow-hidden");
        });

		// marquee Home page
		  $('.block-marquee .marquee-with-options').marquee({
			//speed in milliseconds of the marquee
			speed: 50,
			//gap in pixels between the tickers
			gap: 0,
			//gap in pixels between the tickers
			delayBeforeStart: 0,
			//'left' or 'right'
			direction: 'left',
			//true or false - should the marquee be duplicated to show an effect of continues flow
			duplicated: true,
			//on hover pause the marquee - using jQuery plugin https://github.com/tobia/Pause
			pauseOnHover: true
		  });
		
        /** 
         * Click show filter on mobile
         */

        $("body").on('click', '#btn-sidebar-cat,#btn-sidebar, #close-sidebar,.sidebar-overlay', function () {
            $('html').toggleClass('show-sidebar');
			$(document).trigger("afterAjaxLazyLoad");
        });
		$("body").on('click', '#btn-filter,#close-filter', function () {
            $('html').toggleClass('show-filter');  
			$(document).trigger("afterAjaxLazyLoad");
        });
		if ($('.action-click-dropcart').length) {
			$('.header-container .minicart-wrapper .action.showcart').click(function () {
				$('html').toggleClass('show-minicart-drop');  
			});
			 $("body").on('click', '#btn-cart-close,.minicart-overlay', function () {
				 $('html').toggleClass('show-minicart-drop');
			}); 
		}	
		
		
         /**
         * Sidebar title
         */
		$('.sidebar .block:not(.filter) .block-title').click(function () {
            $(this).next().slideToggle(200);
            $(this).toggleClass('active');
        });
        /**
         * Add parent class to megamenu item
         */

        $('.sm_megamenu_menu > li > div').parent().addClass('parent-item');

        /**
         * Menu ontop
         */

        if ($('.enable-stickymenu').length) {
            var wd = $(window);
            if ($('.ontop-element').length) {
                var menu_offset_top = $('.ontop-element').offset().top + 50;

                function processScroll() {
                    var scrollTop = wd.scrollTop();
                    if (scrollTop >= menu_offset_top) {
                        $('.ontop-element').addClass('menu-on-top');
                        $('body').addClass('body-on-top');
                    } else if (scrollTop <= menu_offset_top) {
                        $('.ontop-element').removeClass('menu-on-top');
                        $('body').removeClass('body-on-top');
                    }
                }

                processScroll();
                wd.scroll(function () {
                    processScroll();
                });
            }
        }

        /**
         * Menu sidebar mobile
         */

        $('.mobile-menu #btn-nav-mobile, .nav-overlay').click(function () {
            $('body').toggleClass('show-sidebar-nav');
        });

        $('div[data-move="customer-mobile"] > .header.links').clone().appendTo('#customer-mobile');


        var menuType = $('#sm-header-mobile').data('menutype');

        if (menuType == 'megamenu') {
            $('.btn-submobile').click(function () {
                $(this).prev().slideToggle(200);
                $(this).toggleClass('btnsub-active');
                $(this).parent().toggleClass('parent-active');
                $(".sm-megamenu-child img").trigger("unveil");
            });

            function cloneMegaMenu() {
                var breakpoints = $('#sm-header-mobile').data('breakpoint');
                var doc_width = $(window).width();
                if (doc_width <= breakpoints) {
                    var horizontalMegamenu = $('.sm_megamenu_wrapper_horizontal_menu .horizontal-type');
                    var verticalMegamenu = $('.sm_megamenu_wrapper_vertical_menu .vertical-type');
                    $('#navigation-mobile').append(horizontalMegamenu);
                    $('#navigation-mobile').append(verticalMegamenu);
					
					// megamenu mobile
					$('.navigation-mobile .feature-item').has('ul').append('<span class="touch-button"></span>'); 
					$('.navigation-mobile .feature-item .touch-button').click(function () {
							$(this).prev().slideToggle(200);
							$(this).toggleClass('active');
							$(this).parent().toggleClass('parent-active'); 
					});
                } else {
                    var horizontalMegamenu = $('#navigation-mobile .horizontal-type');
                    var verticalMegamenu = $('#navigation-mobile .vertical-type');
                    $('.sm_megamenu_wrapper_horizontal_menu .sambar-inner .mega-content').append(horizontalMegamenu);
                    $('.sm_megamenu_wrapper_vertical_menu .sambar-inner .mega-content').append(verticalMegamenu);
                }
            }

            cloneMegaMenu();

            $(window).resize(function () {
                cloneMegaMenu();
            });
        } else {
            $('.navigation-mobile > ul li').has('ul').append('<span class="touch-button"><span>open</span></span>');
			
            $('.touch-button').click(function () {
                $(this).prev().slideToggle(200);
                $(this).toggleClass('active');
                $(this).parent().toggleClass('parent-active');
            });
        }
		/**
         * Clone search mobile
         */
		 function cloneSearch() {
            var breakpoints = $('#sm-header-mobile').data('breakpoint');
            var doc_width = $(window).width();
            if (doc_width <= breakpoints) {
                var searchDesktop = $('.popup-search div[data-move="search-mobile"]');
                $('#search-mobile-dropdown').append(searchDesktop);
            } else {
				var searchDesktop = $('div[data-move="search-mobile"]');
                $('.header-container .popup-search').prepend(searchDesktop);
            } 
        }

        cloneSearch();

        $(window).resize(function () {
            cloneSearch();
        });

        /**
         * Clone minicart mobile
         */

        function cloneCart() {
            var breakpoints = $('#sm-header-mobile').data('breakpoint');
            var doc_width = $(window).width();
            if (doc_width <= breakpoints) {
                var cartDesktop = $('div[data-move="minicart-mobile"]  .minicart-wrapper');
                $('#minicart-mobile').append(cartDesktop);
            } else {
                var cartMobile = $('#minicart-mobile .minicart-wrapper');
                $('div[data-move="minicart-mobile"]').append(cartMobile);
            } 
        }

        cloneCart();

        $(window).resize(function () {
            cloneCart();
        });
		$(window).resize(function () {
			 if ($('.product-2-style, .product-5-style').length) {
				buttonResize();
			}
		});	 

       // $('.product-info-main .product-social-links').appendTo('.product-info-main .box-tocart .addtocart-button');
		$('.product-info-main .percent-price').appendTo('.product-info-main .price-box');

      
		function buttonResize(){
			setTimeout(function() { 
                $('.product-item-info').each(function(){
						var imageHeight = $(this).find('.product-image-container').height();
						console.log(imageHeight);
						$(this).find('.product-item-details .actions-secondary').css({'top': -1 * (imageHeight - 15)});
					

                });   
			}, 1000);	
		}
		
		/**
         * Hover product style 2 
         */
        if ($('.product-2-style, .product-5-style').length) {
            $("body").on('mouseenter touchstart', '.products-grid .product-item-info', function () {
                var imageHeight = $(this).find('.product-image-container').height();
                $(this).find('.product-item-details .actions-secondary').css({'top': -1 * (imageHeight - 15)});
            });
            buttonResize();
            
        }
		
		/**
         *  Language - currency
         */

		 $('.header-2-style .page-footer .language-currency').clone().appendTo('.header-2-style .right-header-top');
         $('.header-4-style .page-footer .language-currency').clone().appendTo('.header-4-style .header-content-left');
         $('.header-5-style .page-footer .language-currency').clone().appendTo('.header-5-style .header-content-left'); 
         $('.header-6-style .page-footer .language-currency').clone().appendTo('.header-6-style .right-header-top');
         $('.header-7-style .page-footer .language-currency').clone().appendTo('.header-7-style .right-header-top');
         $('.header-8-style .page-footer .language-currency').clone().appendTo('.header-8-style .right-header-top');
		 $('.header-10-style .page-footer .language-currency').clone().appendTo('.header-10-style .right-header-top');	
		 $('.product-info-main .product-social-links').appendTo('.product-info-main .box-tocart .actions');	 		 
		 
        /**
         * Hover item menu init lazyload image
         */

        $(".sm_megamenu_menu > li").hover(function () {
            $(document).trigger("afterAjaxLazyLoad");
        });
		
		 // Scroll tabs
		 
		$('.hdt-accordion-link-list-faqs').onePageNav();
		
		if ($('.hdt-accordion-link-list-faqs').length) {
            
            $window = $(window);
            var menu_offset_top = document.querySelector('.breadcrumbs-title').offsetHeight + document.querySelector('.header-container').offsetHeight;
            var height_left_tab = document.querySelector('.hdt-accordion-link-list-faqs').offsetHeight;
            
            function processScrollFaqs() {
                var scrollTop = $window.scrollTop();
                var height_right_tab = menu_offset_top + document.querySelector('.col-left-faqs').offsetHeight;
                var height_scroll_over_tab = height_right_tab - height_left_tab - 30;
                
                if(scrollTop >= height_scroll_over_tab){
                    $('.hdt-accordion-link-list-faqs').removeClass('faqs-on-top');
                } else {
                    if (  scrollTop >= menu_offset_top  ) {
                        $('.hdt-accordion-link-list-faqs').addClass('faqs-on-top');	;
                    } else {
                        $('.hdt-accordion-link-list-faqs').removeClass('faqs-on-top');
                    } 
                }
                	
			}
            
			processScrollFaqs();
			$window.scroll(function(){
				processScrollFaqs();
			});
		}
            
       
		/**
         * Countdown static
         */

        function _countDownStatic(date, id) {
            var dateNow = new Date();
            var amount = date.getTime() - dateNow.getTime();
            delete dateNow;
            if (amount < 0) {
                id.html("Now!");
            } else {
                var days = 0;
                hours = 0;
                mins = 0;
                secs = 0;
                out = "";
                amount = Math.floor(amount / 1000);
                days = Math.floor(amount / 86400);
                amount = amount % 86400;
                hours = Math.floor(amount / 3600);
                amount = amount % 3600;
                mins = Math.floor(amount / 60);
                amount = amount % 60;
                secs = Math.floor(amount);
				if (days<10) {
				  days = "0" + days;
				} else {
				  days = days;
				}
				if (hours<10) {
				  hours = "0" + hours;
				} else {
				  hours = hours;
				}
				if (mins<10) {
				  mins = "0" + mins;
				} else {
				  mins = mins;
				}
				if (secs<10) {
				  secs = "0" + secs;
				}else {
				  secs = secs;
				}
				
                $(".time-day .num-time", id).text(days);
                $(".time-day .title-time", id).text(((days <= 1) ? "Day" : "Days"));
                $(".time-hours .num-time", id).text(hours);
                $(".time-hours .title-time", id).text(((hours <= 1) ? "Hour" : "Hours"));
                $(".time-mins .num-time", id).text(mins);
                $(".time-mins .title-time", id).text(((mins <= 1) ? "Min" : "Mins"));
                $(".time-secs .num-time", id).text(secs);
                $(".time-secs .title-time", id).text(((secs <= 1) ? "Sec" : "Secs"));
                setTimeout(function () {
                    _countDownStatic(date, id)
                }, 1000);
            }
        }
		

        $(".countdown-static").each(function () {
            var timer = $(this).data('timer');
            var data = new Date(timer);
            _countDownStatic(data, $(this));
        });
		
		// trigger copy event on click
			$('#copy').on('click', function(event) {
			  console.log(event);
			  copyToClipboard(event);
			});
			function copyToClipboard(e) {
			  var
				t = e.target, 
				c = t.dataset.copytarget,
				inp = (c ? document.querySelector(c) : null);
			  console.log(inp);
			  if (inp && inp.select) {
				inp.select();
				try {
				  document.execCommand('copy');
				  inp.blur();
				  t.classList.add('copied');
				  setTimeout(function() {
					t.classList.remove('copied');
				  }, 1500);
				} catch (err) {
				  alert('please press Ctrl/Cmd+C to copy');
				}

			  }

			}
		// reponsiver
		$(".footer-block .footer-block-title").click(function () { 
				$(this).parent().toggleClass("active");
				 $(this).next().slideToggle(200); 
		});
		
		/**
         * Bottom sticky mobile
		 
         */

        $(".sticky-bottom .button-sticky-bottom").click(function () {
            $(".sticky-bottom .block-bottom").removeClass("active");
            $('html').removeClass("minicart-active")

            if ($(this).hasClass("active")) {
                $("#" + $(this).attr("data-drop")).removeClass("active");
                $(this).removeClass("active");
                $("html").removeClass("overflow-hidden");
                return;
            } else {
                $(".sticky-bottom .button-sticky-bottom").removeClass("active");
                $("#" + $(this).attr("data-drop")).toggleClass("active");
                $(this).addClass("active");
                if ($("#" + $(this).attr("data-drop")).hasClass("active")) {
                    $("html").addClass("overflow-hidden");
                } else {
                    $("html").removeClass("overflow-hidden");
                }
            }
        });

        $(".sticky-bottom .close-sticky-bottom").click(function () {
            var el = $(this).attr("data-drop");
            $("#" + el).removeClass("active");
            $(".sticky-bottom .button-sticky-bottom").removeClass("active");
            $("html").removeClass("overflow-hidden");
        });
		// remove title
		$('.products-grid:not(.wishlist) .product-item-info .product-item-details .product-item-actions a, .products-grid:not(.wishlist) .product-item-info .product-item-details .product-item-actions button').attr('title', '');
		
		$('.breadcrumbs-title').each(function(){
		  if($(this).children().length == 0){
			$(this).hide();
		  }
		});
		
		// button filter 
		if ( $(".block.filter").length > 0) {
			$(".filter-mobile-btn").css("display", " block");
		} else {
				$(".filter-mobile-btn").css("display", " none");
		}
		 
		
		
		
    });
