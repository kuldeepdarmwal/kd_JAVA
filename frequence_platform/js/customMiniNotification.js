vljQuery(function() {
  vljQuery.miniNotification = function(element, options) {
    var appendCloseButton, getHiddenCssProps, getVisibleCssProps, setState, state, wrapInnerElement,
      _this = this;
    this.defaults = {
      position: 'bottom',
      show: true,
      effect: 'slide',
      opacity: 0.95,
      time: 10000,
      showSpeed: 600,
      hideSpeed: 450,
      showEasing: '',
      hideEasing: '',
      innerDivClass: 'inner',
      closeButton: false,
      closeButtonText: 'close',
      closeButtonClass: 'close',
      hideOnClick: true,
      onLoad: function() {},
      onVisible: function() {},
      onHide: function() {},
      onHidden: function() {vljQuery('#vldemonotification').css("display", "none")}
    };
    state = '';
    this.settings = {};
    this.$element = vljQuery(element);
    setState = function(_state) {
      return state = _state;
    };
    getHiddenCssProps = function() {
      var css, position;
      position = (_this.getSetting('effect')) === 'slide' ? 0 - _this.$element.outerHeight() : 0;
      css = {};
      if ((_this.getSetting('position')) === 'bottom') {
        css['bottom'] = position;
      } else {
        css['top'] = position;
      }
      if ((_this.getSetting('effect')) === 'fade') {
        css['opacity'] = 0;
      }
      return css;
    };
    getVisibleCssProps = function() {
      var css;
      css = {
        'opacity': _this.getSetting('opacity')
      };
      if ((_this.getSetting('position')) === 'bottom') {
        css['bottom'] = 0;
      } else {
        css['top'] = 0;
      }
      return css;
    };
    wrapInnerElement = function() {
      _this.$elementInner = vljQuery('<div />', {
        'class': _this.getSetting('innerDivClass')
      });
      return _this.$element.wrapInner(_this.$elementInner);
    };
    appendCloseButton = function() {
      var $closeButton;
      $closeButton = vljQuery('<a />', {
        'class': _this.getSetting('closeButtonClass'),
        'html': _this.getSetting('closeButtonText')
      });
      _this.$element.children().append($closeButton);
      return $closeButton.bind('click', function() {
        return _this.hide();
      });
    };
    this.getState = function() {
      return state;
    };
    this.getSetting = function(settingKey) {
      return this.settings[settingKey];
    };
    this.callSettingFunction = function(functionName) {
      return this.settings[functionName](element);
    };
    this.init = function() {
      var _this = this;
      setState('hidden');
      this.settings = vljQuery.extend({}, this.defaults, options);
      if (this.$element.length) {
        wrapInnerElement();
        if (this.getSetting('closeButton')) {
          appendCloseButton();
        }
        this.$element.css(getHiddenCssProps()).css({
          display: 'inline'
        });
        if (this.getSetting('show')) {
          this.show();
        }
        if (this.getSetting('hideOnClick')) {
          return this.$element.bind('click', function() {
            if (_this.getState() !== 'hiding') {
              return _this.hide();
            }
          });
        }
      }
    };
    this.show = function() {
      var _this = this;
      if (this.getState() !== 'showing' && this.getState() !== 'visible') {
        setState('showing');
        this.callSettingFunction('onLoad');
        return this.$element.animate(getVisibleCssProps(), this.getSetting('showSpeed'), this.getSetting('showEasing'), function() {
          setState('visible');
          _this.callSettingFunction('onVisible');
          return setTimeout((function() {
            return _this.hide();
          }), _this.settings.time);
        });
      }
    };
    this.hide = function() {
      var _this = this;
      if (this.getState() !== 'hiding' && this.getState() !== 'hidden') {
        setState('hiding');
        this.callSettingFunction('onHide');
        return this.$element.animate(getHiddenCssProps(), this.getSetting('hideSpeed'), this.getSetting('hideEasing'), function() {
          setState('hidden');
          return _this.callSettingFunction('onHidden');
        });
      }
    };
    this.init();
    return this;
  };
  return vljQuery.fn.miniNotification = function(options) {
    return this.each(function() {
      var plugin;
      plugin = (vljQuery(this)).data('miniNotification');
      if (plugin === void 0) {
        plugin = new vljQuery.miniNotification(this, options);
        return (vljQuery(this)).data('miniNotification', plugin);
      } else {
        return plugin.show();
      }
    });
  };
});

function frwvla(){
	vljQuery.expr[":"].is300x250 = function(obj){
		return ((vljQuery(obj).width() == 300) && (vljQuery(obj).height() == 250));
	};
	vljQuery(":is300x250").each(function(){
		vljQuery(this)[0].outerHTML = vlpreviewadtags;
	});
}
function initchdnjs(){
	console.log("calling notification");
	vljQuery("#vldemonotification").miniNotification(
		{closeButton:true, 
		 closeButtonText: "[hide]", 
		 time:1500000, opacity:0.88
		});
}
vljQuery(document).ready(function() {
	if(ad_shown != 1)	
	{
		frwvla();
	}
	initchdnjs();
});
