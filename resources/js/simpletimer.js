(function ($) {
	var defaluts = {
		day: 0,
		dayDom: '',
		hour: 0,
		hourDom: '',
		minute: 1,
		minuteDom: '',
		second: 0,
		secondDom: '',
		millisecond: 0,
		millisecondDom: '',
		blank: 1000,
		pause: '',
		pauseFun: '',
		goonFun: '',
		endFun: '',
		animation: 'none'
	};

	$.fn.extend({
		stamp: 0,
		intervalObj: {},
		state: 1,
		simpletimer: function (options) {
			var _self = this;
			var options = $.extend({}, defaluts, options);
			_self.stamp = parseInt(options.millisecond) + 10 * parseInt(options.second) + 600 * parseInt(options.minute) + 36000 * parseInt(options.hour) + 864000 * parseInt(options.day);
			_self._timekeeper(options);
			_self.intervalObj = setInterval(function () {
				_self._timekeeper(options);
				if (_self.stamp < 0) {
					_self.state = 0;
					clearInterval(_self.intervalObj);
					options.endFun();
				}
			}, parseInt(options.blank));

			this._pause(options);
		},
		_pause: function (options) {
			var _self = this;

			$(options.pause).on('click', function () {
				if (_self.state === 1) {
					_self.state = 0;
					clearInterval(_self.intervalObj);
					$(this).html('Resume');
					options.pauseFun();
				} else {
					_self.state = 1;
					_self._dida(options);
					options.goonFun();
				}
			});
		},
		_timekeeper: function (options) {
			var _self = this;
			var temp = _self.stamp;
			var day = Math.floor(temp / 864000);
			temp = temp % 864000;
			var hour = Math.floor(temp / 36000);
			temp = temp % 36000;
			var minute = Math.floor(temp / 600);
			temp = temp % 600;
			var second = Math.floor(temp / 10);
			var millisecond = temp % 10;
			$(options.dayDom).html(_self.prefixInteger(day, 2));
			$(options.hourDom).html(_self.prefixInteger(hour, 2));
			$(options.minuteDom).html(_self.prefixInteger(minute, 2));
			$(options.secondDom).html(_self.prefixInteger(second, 2));
			$(options.millisecondDom).html(_self.prefixInteger(millisecond, 1));
			_self.stamp = _self.stamp - (options.blank/100).toFixed(0);
		},
		prefixInteger: function(num, length) {
			return ("0000" + num).substr(-length);
		}
	});
})(jQuery);
