var config = {
    map: {
        '*': {
			'bootstrap': 'js/bootstrap/bootstrap.min',
            'popper': 'js/bootstrap/popper',
			'marquee': 'js/jquery.marquee',
			'jquery.nav': 'js/jquery.nav', 
            'slick': 'js/slick'
        }
    },
    shim: {
        'bootstrap.min': {
            'deps': ['jquery']
        }
    },
    deps: [
        "js/bootstrap/bootstrap.min",
        "js/theme-js"
    ]
};