/*

 File: fw_utils.js
 Copyright (c) 2014 Clemens K. (https://github.com/metacreature)
 
 MIT License
 
 Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:
 
 The above copyright notice and this permission notice shall be included in all
 copies or substantial portions of the Software.
 
 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 SOFTWARE.

*/

try{
	if (top != self) {
		if (WEB_URL && document.location.href.indexOf(WEB_URL) !== 0) {
			top.location.href = WEB_URL;
		} else {
			top.location.href = self.location.href;
		}
	}
	if (WEB_URL && document.location.href.indexOf(WEB_URL) !== 0) {
		top.location.href = WEB_URL;
	}
} catch(e) {}

var array_keys = function(obj) {
	try{
		ret = Object.keys(obj);
	} catch(e) {
		var i, ret = [];
		for (i in obj) {
			ret.push(i);
		}
	}
	return ret;
}

var is_empty = function(mixedVar, zero_is_not_empty) {
	var undef, key, i, len;

	if (typeof mixedVar === 'object') {
		for (key in mixedVar) {
			if (mixedVar.hasOwnProperty(key)) {
				return false;
			}
		}
		return true;
	}
	if (zero_is_not_empty) {
		var emptyValues = [undef, null, false, ''];
	} else {
		var emptyValues = [undef, null, false, 0, '', '0'];
	}
	for (i = 0, len = emptyValues.length; i < len; i++) {
		if (mixedVar === emptyValues[i]) {
			return true;
		}
	}
	return false;
}

var trim = function(sString) {
	return sString.trim();
}

var randInt = function (min, max) 
{
  min = Math.ceil(min);
  max = Math.floor(max);
  return Math.round(Math.random() * (max - min)) + min; 
}

function range(size, startAt) {
	startAt = startAt ? startAt : 0;
	var arr = array_keys(Array(size));
	arr = arr.map(function(i) {return i + startAt});
	if (arr.length == 0) {
		var i = 0, arr = [];
		while (i < size) {
			arr.push(i + startAt);
			i++;
		}
	}
    return arr;
}

var chr = function(s) 
{
	return String.fromCharCode(s);
}

var query_to_object = function(query) 
{
	var i, ret = {}, tmp;
	
	if (query.indexOf('?') !== -1) 
		query = query.split('?')[1];
	query = query.split('#')[0];
	query = query.split('&');
	
	for (i in query) {
		tmp = query[i].split('=');
		if (/\[\]$/.test(tmp[0])) {
			tmp[0] = tmp[0].replace('[]', '');
			if (!ret[tmp[0]]) 
				ret[tmp[0]] = [];
			ret[tmp[0]].push(decodeURIComponent(tmp[1]));
		} else if (/\[([^\]]+)\]$/.test(tmp[0])) {
			tmp[0] = tmp[0].replace(']', '');
			tmp[0] = tmp[0].split('[');
			if (!ret[tmp[0][0]])
				ret[tmp[0][0]] = {};
			ret[tmp[0][0]][tmp[0][1]] = decodeURIComponent(tmp[1]);
		} else {
			ret[tmp[0]] = decodeURIComponent(tmp[1]);
		}
	}
	return ret;
}

var object_to_query = function(obj) 
{
	var i, i2, ret = [];
	for (i in obj) {
		if (obj[i] instanceof Array) {
			for (i2 in obj[i]) {
				ret.push(i+'[]='+encodeURIComponent(obj[i][i2]));
			}
		} else if (typeof obj[i] == 'object') {
			for (i2 in obj[i]) {
				ret.push(i+'['+i2+']='+encodeURIComponent(obj[i][i2]));
			}
		} else {
			ret.push(i+'='+encodeURIComponent(obj[i]+''));
		}
	}
	return ret.join('&');
}

var set_cookie = function (cname, cvalue, exdays) {
	var expires = '';
	if (typeof exdays != 'undefined') {
		var d = new Date();
		d.setTime(d.getTime() + (exdays*24*60*60*1000));
		expires = "; expires="+ d.toUTCString();
	}
	document.cookie = cname + "=" + cvalue + expires + "; path=/; domain="+WEB_DOMAIN+"; samesite=Strict;";
}


var delete_cookie = function (cname) {
	document.cookie = cname + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain="+WEB_DOMAIN+"; samesite=Strict;";
}

var get_cookie = function (cname) {
  var name = cname + "=";
  var decodedCookie = decodeURIComponent(document.cookie);
  var ca = decodedCookie.split(';');
  for(var i = 0; i <ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

var html_special_chars = function (text) {
  if (typeof text != 'string') {
	return text;
  }
  var map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };

  return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

var nl2br = function(text) {
	return text.replace(new RegExp("\n",'g'), '<br>');
}

function shuffleString(str) {
	return [...str].sort(()=>Math.random()-.5).join('');
}

function generatePassword(length) {
	let charset_lowercase = 'abcdefghijklmnopqrstuvwxyz';
	let charset_uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	let charset_number = '01234567890123456789';
	let charset_special = '![]{}<>-_.,;:#*+~|@^$%&/()=?';
	let charset = shuffleString(shuffleString(charset_lowercase + charset_uppercase + charset_special + charset_number));

	let password = '';

	password += charset_lowercase.charAt(randInt(0, charset_lowercase.length -1));
	password += charset_uppercase.charAt(randInt(0, charset_uppercase.length -1));
	password += charset_number.charAt(randInt(0, charset_number.length -1));
	password += charset_special.charAt(randInt(0, charset_special.length -1));

    while(password.length < length) {
		password += charset.charAt(randInt(0, charset.length -1));
    }

    return shuffleString(shuffleString(password));
}

function fallbackCopyTextToClipboard(text) {
  
}
async function copyTextToClipboard(text) {
	try {
		let res = await navigator.clipboard.writeText(text).then(function() {
			console.log('Async: Copying text to clipboard was successful!');
			return true;
		}, function(err) {
			console.error('Async: Could not copy text to clipboard');
			return false;
		});
		if (res) {
			return true;
		}
	} catch (err) {}

	var textArea = document.createElement("textarea");
	textArea.value = text;

	// Avoid scrolling to bottom
	textArea.style.top = "-1000";
	textArea.style.left = "-1000";
	textArea.style.width = "100";
	textArea.style.height = "100";
	textArea.style.position = "fixed";

	document.body.appendChild(textArea);
	textArea.focus();
	textArea.select();

	try {
		if(document.execCommand('copy')) {
			document.body.removeChild(textArea);
			console.log('Copying text to clipboard was successful!');
			return true;
		}
	} catch (err) {}

	document.body.removeChild(textArea);
	console.error('Fallback: Could not copy text to clipboard');
	return false;
}

function scrollVisible(ele, extra_margin) {
	extra_margin = extra_margin || 20;
	var scroll_top = document.querySelector("html").scrollTop;
	var body_style = window.getComputedStyle(document.querySelector("body"));
	var body_top = parseInt(body_style.getPropertyValue('padding-top').replace('px', ''));
	var body_bottom = parseInt(body_style.getPropertyValue('padding-bottom').replace('px', ''));

	var response_rect = ele.getBoundingClientRect();
	var max_top = response_rect.top + scroll_top - (body_top + extra_margin);
	var min_bottom = response_rect.top + scroll_top + (response_rect.bottom - response_rect.top) - window.innerHeight + (body_bottom + extra_margin);

	if (scroll_top > max_top) {
		window.scrollTo({
			top: max_top,
			behavior: "smooth",
		});
	}else if (scroll_top < min_bottom) {
		if (max_top > min_bottom) {
			window.scrollTo({
				top: min_bottom,
				behavior: "smooth",
			});
		} else {
			window.scrollTo({
				top: max_top,
				behavior: "smooth",
			});
		}
	}
}