/*! For license information please see admin-general.js.LICENSE.txt */
(()=>{var e={808:(e,t,n)=>{var s,i,o;void 0===(i="function"==typeof(s=o=function(){function e(){for(var e=0,t={};e<arguments.length;e++){var n=arguments[e];for(var s in n)t[s]=n[s]}return t}function t(e){return e.replace(/(%[0-9A-Z]{2})+/g,decodeURIComponent)}return function n(s){function i(){}function o(t,n,o){if("undefined"!=typeof document){"number"==typeof(o=e({path:"/"},i.defaults,o)).expires&&(o.expires=new Date(1*new Date+864e5*o.expires)),o.expires=o.expires?o.expires.toUTCString():"";try{var r=JSON.stringify(n);/^[\{\[]/.test(r)&&(n=r)}catch(e){}n=s.write?s.write(n,t):encodeURIComponent(String(n)).replace(/%(23|24|26|2B|3A|3C|3E|3D|2F|3F|40|5B|5D|5E|60|7B|7D|7C)/g,decodeURIComponent),t=encodeURIComponent(String(t)).replace(/%(23|24|26|2B|5E|60|7C)/g,decodeURIComponent).replace(/[\(\)]/g,escape);var l="";for(var a in o)o[a]&&(l+="; "+a,!0!==o[a]&&(l+="="+o[a].split(";")[0]));return document.cookie=t+"="+n+l}}function r(e,n){if("undefined"!=typeof document){for(var i={},o=document.cookie?document.cookie.split("; "):[],r=0;r<o.length;r++){var l=o[r].split("="),a=l.slice(1).join("=");n||'"'!==a.charAt(0)||(a=a.slice(1,-1));try{var c=t(l[0]);if(a=(s.read||s)(a,c)||t(a),n)try{a=JSON.parse(a)}catch(e){}if(i[c]=a,e===c)break}catch(e){}}return e?i[e]:i}}return i.set=o,i.get=function(e){return r(e,!1)},i.getJSON=function(e){return r(e,!0)},i.remove=function(t,n){o(t,"",e(n,{expires:-1}))},i.defaults={},i.withConverter=n,i}((function(){}))})?s.call(t,n,t,e):s)||(e.exports=i),e.exports=o()},204:e=>{function t(e,t){if(!e)throw new Error(t||"AssertionError")}t.notEqual=function(e,n,s){t(e!=n,s)},t.notOk=function(e,n){t(!e,n)},t.equal=function(e,n,s){t(e==n,s)},t.ok=t,e.exports=t},559:(e,t,n)=>{var s=n(69),i=n(999),o=n(204);function r(e){if(!(this instanceof r))return new r(e);this._name=e||"nanobus",this._starListeners=[],this._listeners={}}e.exports=r,r.prototype.emit=function(e){o.ok("string"==typeof e||"symbol"==typeof e,"nanobus.emit: eventName should be type string or symbol");for(var t=[],n=1,s=arguments.length;n<s;n++)t.push(arguments[n]);var r=i(this._name+"('"+e.toString()+"')"),l=this._listeners[e];return l&&l.length>0&&this._emit(this._listeners[e],t),this._starListeners.length>0&&this._emit(this._starListeners,e,t,r.uuid),r(),this},r.prototype.on=r.prototype.addListener=function(e,t){return o.ok("string"==typeof e||"symbol"==typeof e,"nanobus.on: eventName should be type string or symbol"),o.equal(typeof t,"function","nanobus.on: listener should be type function"),"*"===e?this._starListeners.push(t):(this._listeners[e]||(this._listeners[e]=[]),this._listeners[e].push(t)),this},r.prototype.prependListener=function(e,t){return o.ok("string"==typeof e||"symbol"==typeof e,"nanobus.prependListener: eventName should be type string or symbol"),o.equal(typeof t,"function","nanobus.prependListener: listener should be type function"),"*"===e?this._starListeners.unshift(t):(this._listeners[e]||(this._listeners[e]=[]),this._listeners[e].unshift(t)),this},r.prototype.once=function(e,t){o.ok("string"==typeof e||"symbol"==typeof e,"nanobus.once: eventName should be type string or symbol"),o.equal(typeof t,"function","nanobus.once: listener should be type function");var n=this;return this.on(e,(function s(){t.apply(n,arguments),n.removeListener(e,s)})),this},r.prototype.prependOnceListener=function(e,t){o.ok("string"==typeof e||"symbol"==typeof e,"nanobus.prependOnceListener: eventName should be type string or symbol"),o.equal(typeof t,"function","nanobus.prependOnceListener: listener should be type function");var n=this;return this.prependListener(e,(function s(){t.apply(n,arguments),n.removeListener(e,s)})),this},r.prototype.removeListener=function(e,t){return o.ok("string"==typeof e||"symbol"==typeof e,"nanobus.removeListener: eventName should be type string or symbol"),o.equal(typeof t,"function","nanobus.removeListener: listener should be type function"),"*"===e?(this._starListeners=this._starListeners.slice(),n(this._starListeners,t)):(void 0!==this._listeners[e]&&(this._listeners[e]=this._listeners[e].slice()),n(this._listeners[e],t));function n(e,t){if(e){var n=e.indexOf(t);return-1!==n?(s(e,n,1),!0):void 0}}},r.prototype.removeAllListeners=function(e){return e?"*"===e?this._starListeners=[]:this._listeners[e]=[]:(this._starListeners=[],this._listeners={}),this},r.prototype.listeners=function(e){var t="*"!==e?this._listeners[e]:this._starListeners,n=[];if(t)for(var s=t.length,i=0;i<s;i++)n.push(t[i]);return n},r.prototype._emit=function(e,t,n,s){if(void 0!==e&&0!==e.length){void 0===n&&(n=t,t=null),t&&(n=void 0!==s?[t].concat(n,s):[t].concat(n));for(var i=e.length,o=0;o<i;o++){var r=e[o];r.apply(r,n)}}}},61:(e,t,n)=>{var s=n(204),i="undefined"!=typeof window;function o(e){this.hasWindow=e,this.hasIdle=this.hasWindow&&window.requestIdleCallback,this.method=this.hasIdle?window.requestIdleCallback.bind(window):this.setTimeout,this.scheduled=!1,this.queue=[]}o.prototype.push=function(e){s.equal(typeof e,"function","nanoscheduler.push: cb should be type function"),this.queue.push(e),this.schedule()},o.prototype.schedule=function(){if(!this.scheduled){this.scheduled=!0;var e=this;this.method((function(t){for(;e.queue.length&&t.timeRemaining()>0;)e.queue.shift()(t);e.scheduled=!1,e.queue.length&&e.schedule()}))}},o.prototype.setTimeout=function(e){setTimeout(e,0,{timeRemaining:function(){return 1}})},e.exports=function(){var e;return i?(window._nanoScheduler||(window._nanoScheduler=new o(!0)),e=window._nanoScheduler):e=new o,e}},999:(e,t,n)=>{var s,i=n(61)(),o=n(204);r.disabled=!0;try{s=window.performance,r.disabled="true"===window.localStorage.DISABLE_NANOTIMING||!s.mark}catch(e){}function r(e){if(o.equal(typeof e,"string","nanotiming: name should be type string"),r.disabled)return l;var t=(1e4*s.now()).toFixed()%Number.MAX_SAFE_INTEGER,n="start-"+t+"-"+e;function a(o){var r="end-"+t+"-"+e;s.mark(r),i.push((function(){var i=null;try{var l=e+" ["+t+"]";s.measure(l,n,r),s.clearMarks(n),s.clearMarks(r)}catch(e){i=e}o&&o(i,e)}))}return s.mark(n),a.uuid=t,a}function l(e){e&&i.push((function(){e(new Error("nanotiming: performance API unavailable"))}))}e.exports=r},69:e=>{"use strict";e.exports=function(e,t,n){var s,i=e.length;if(!(t>=i||0===n)){var o=i-(n=t+n>i?i-t:n);for(s=t;s<o;++s)e[s]=e[s+n];e.length=o}}}},t={};function n(s){var i=t[s];if(void 0!==i)return i.exports;var o=t[s]={exports:{}};return e[s](o,o.exports,n),o.exports}n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(e,t)=>{for(var s in t)n.o(t,s)&&!n.o(e,s)&&Object.defineProperty(e,s,{enumerable:!0,get:t[s]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{"use strict";let e=n(808);class t{constructor(e){this.element=e,this.init()}init(){if(this.element.classList.contains("-closable")){const t=this.element.querySelector(".ac-section__header");if(t&&t.addEventListener("click",(()=>{this.toggle()})),this.isStorable()){let t=e.get(this.getCookieKey());void 0!==t&&(1===parseInt(t)?this.open:this.close())}}}getCookieKey(){return`ac-section_${this.getSectionId()}`}getSectionId(){return this.element.dataset.section}isStorable(){return void 0!==this.element.dataset.section}toggle(){this.isOpen()?this.close():this.open()}isOpen(){return!this.element.classList.contains("-closed")}open(){this.element.classList.remove("-closed"),this.isStorable()&&e.set(this.getCookieKey(),1)}close(){this.element.classList.add("-closed"),this.isStorable()&&e.set(this.getCookieKey(),0)}}class s{constructor(e,t=""){this.element=e,this.content=t||e.dataset.acTip,this.tip=i(this.content),this.initEvents()}initEvents(){"1"!==this.element.dataset.acTooltipInit&&(this.element.dataset.acTooltipInit="1",this.element.addEventListener("mouseenter",(()=>{const e=document.body.getBoundingClientRect(),t=this.element.getBoundingClientRect();document.body.appendChild(this.tip),this.tip.style.left=t.left-e.left+this.element.offsetWidth/2+"px",this.tip.style.top=t.top-e.top+this.element.offsetHeight+"px",this.tip.classList.add("hover")})),this.element.addEventListener("mouseleave",(()=>{this.tip.classList.remove("hover"),document.body.contains(this.tip)&&document.body.removeChild(this.tip)})),this.element.removeAttribute("title"))}}const i=e=>{let t=document.createElement("div");return t.classList.add("ac-tooltip"),t.innerHTML=e,t},o=class{constructor(){this.init()}init(){document.querySelectorAll(".ac-tooltip").forEach((e=>e.remove())),document.querySelectorAll("[data-ac-tip]").forEach((e=>{new s(e)}))}};function r(){}function l(e){return e()}function a(){return Object.create(null)}function c(e){e.forEach(l)}function u(e){return"function"==typeof e}function h(e,t){return e!=e?t==t:e!==t||e&&"object"==typeof e||"function"==typeof e}function d(e){return 0===Object.keys(e).length}new Set;const p="undefined"!=typeof window?window:"undefined"!=typeof globalThis?globalThis:global;class f{constructor(e){this.options=e,this._listeners="WeakMap"in p?new WeakMap:void 0}observe(e,t){return this._listeners.set(e,t),this._getObserver().observe(e,this.options),()=>{this._listeners.delete(e),this._observer.unobserve(e)}}_getObserver(){var e;return null!==(e=this._observer)&&void 0!==e?e:this._observer=new ResizeObserver((e=>{var t;for(const n of e)f.entries.set(n.target,n),null===(t=this._listeners.get(n.target))||void 0===t||t(n)}))}}f.entries="WeakMap"in p?new WeakMap:void 0;let m,v=!1;function y(e,t){e.appendChild(t)}function g(e,t,n){e.insertBefore(t,n||null)}function b(e){e.parentNode&&e.parentNode.removeChild(e)}function w(e){return document.createElement(e)}function _(e){return document.createTextNode(e)}function $(){return _(" ")}function E(e,t,n,s){return e.addEventListener(t,n,s),()=>e.removeEventListener(t,n,s)}function C(e,t,n){null==n?e.removeAttribute(t):e.getAttribute(t)!==n&&e.setAttribute(t,n)}function L(e,t,n){e.classList[n?"add":"remove"](t)}function k(e){m=e}new Map;const S=[],x=[];let O=[];const A=[],q=Promise.resolve();let I=!1;function T(e){O.push(e)}const N=new Set;let H=0;function M(){if(0!==H)return;const e=m;do{try{for(;H<S.length;){const e=S[H];H++,k(e),R(e.$$)}}catch(e){throw S.length=0,H=0,e}for(k(null),S.length=0,H=0;x.length;)x.pop()();for(let e=0;e<O.length;e+=1){const t=O[e];N.has(t)||(N.add(t),t())}O.length=0}while(S.length);for(;A.length;)A.pop()();I=!1,N.clear(),k(e)}function R(e){if(null!==e.fragment){e.update(),c(e.before_update);const t=e.dirty;e.dirty=[-1],e.fragment&&e.fragment.p(e.ctx,t),e.after_update.forEach(T)}}const B=new Set;let P;function j(e,t){const n=e.$$;null!==n.fragment&&(function(e){const t=[],n=[];O.forEach((s=>-1===e.indexOf(s)?t.push(s):n.push(s))),n.forEach((e=>e())),O=t}(n.after_update),c(n.on_destroy),n.fragment&&n.fragment.d(t),n.on_destroy=n.fragment=null,n.ctx=[])}function F(e,t,n,s,i,o,h,d=[-1]){const p=m;k(e);const f=e.$$={fragment:null,ctx:[],props:o,update:r,not_equal:i,bound:a(),on_mount:[],on_destroy:[],on_disconnect:[],before_update:[],after_update:[],context:new Map(t.context||(p?p.$$.context:[])),callbacks:a(),dirty:d,skip_bound:!1,root:t.target||p.$$.root};h&&h(f.root);let y=!1;if(f.ctx=n?n(e,t.props||{},((t,n,...s)=>{const o=s.length?s[0]:n;return f.ctx&&i(f.ctx[t],f.ctx[t]=o)&&(!f.skip_bound&&f.bound[t]&&f.bound[t](o),y&&function(e,t){-1===e.$$.dirty[0]&&(S.push(e),I||(I=!0,q.then(M)),e.$$.dirty.fill(0)),e.$$.dirty[t/31|0]|=1<<t%31}(e,t)),n})):[],f.update(),y=!0,c(f.before_update),f.fragment=!!s&&s(f.ctx),t.target){if(t.hydrate){v=!0;const e=(_=t.target,Array.from(_.childNodes));f.fragment&&f.fragment.l(e),e.forEach(b)}else f.fragment&&f.fragment.c();t.intro&&((g=e.$$.fragment)&&g.i&&(B.delete(g),g.i(w))),function(e,t,n,s){const{fragment:i,after_update:o}=e.$$;i&&i.m(t,n),s||T((()=>{const t=e.$$.on_mount.map(l).filter(u);e.$$.on_destroy?e.$$.on_destroy.push(...t):c(t),e.$$.on_mount=[]})),o.forEach(T)}(e,t.target,t.anchor,t.customElement),v=!1,M()}var g,w,_;k(p)}new Set(["allowfullscreen","allowpaymentrequest","async","autofocus","autoplay","checked","controls","default","defer","disabled","formnovalidate","hidden","inert","ismap","loop","multiple","muted","nomodule","novalidate","open","playsinline","readonly","required","reversed","selected"]),"function"==typeof HTMLElement&&(P=class extends HTMLElement{constructor(){super(),this.attachShadow({mode:"open"})}connectedCallback(){const{on_mount:e}=this.$$;this.$$.on_disconnect=e.map(l).filter(u);for(const e in this.$$.slotted)this.appendChild(this.$$.slotted[e])}attributeChangedCallback(e,t,n){this[e]=n}disconnectedCallback(){c(this.$$.on_disconnect)}$destroy(){j(this,1),this.$destroy=r}$on(e,t){if(!u(t))return r;const n=this.$$.callbacks[e]||(this.$$.callbacks[e]=[]);return n.push(t),()=>{const e=n.indexOf(t);-1!==e&&n.splice(e,1)}}$set(e){this.$$set&&!d(e)&&(this.$$.skip_bound=!0,this.$$set(e),this.$$.skip_bound=!1)}});class W{$destroy(){j(this,1),this.$destroy=r}$on(e,t){if(!u(t))return r;const n=this.$$.callbacks[e]||(this.$$.callbacks[e]=[]);return n.push(t),()=>{const e=n.indexOf(t);-1!==e&&n.splice(e,1)}}$set(e){this.$$set&&!d(e)&&(this.$$.skip_bound=!0,this.$$set(e),this.$$.skip_bound=!1)}}function D(e){let t,n,s,i,o,l,a;return{c(){t=w("div"),n=w("div"),s=$(),i=w("div"),C(n,"class","ac-pointer-modal__arrow"),C(n,"data-pos",e[1]),C(i,"class","ac-pointer-modal__content"),C(t,"class",o="ac-pointer-modal -"+e[1])},m(o,r){g(o,t,r),y(t,n),y(t,s),y(t,i),i.innerHTML=e[0],l||(a=[E(t,"mouseenter",e[6]),E(t,"mouseleave",e[7])],l=!0)},p(e,[s]){2&s&&C(n,"data-pos",e[1]),1&s&&(i.innerHTML=e[0]),2&s&&o!==(o="ac-pointer-modal -"+e[1])&&C(t,"class",o)},i:r,o:r,d(e){e&&b(t),l=!1,c(a)}}}function U(e,t,n){let{content:s=""}=t,{position:i=""}=t,o=!1,{closeHandler:r}=t;const l=()=>{n(2,o=!1),r()};return e.$$set=e=>{"content"in e&&n(0,s=e.content),"position"in e&&n(1,i=e.position),"closeHandler"in e&&n(4,r=e.closeHandler)},[s,i,o,l,r,()=>o,()=>n(2,o=!0),()=>l()]}const J=class extends W{constructor(e){super(),F(this,e,U,D,h,{content:0,position:1,closeHandler:4,isOnElement:5})}get isOnElement(){return this.$$.ctx[5]}};class K{constructor(e,t){var n,s;this.element=e,this.target=t,this.width=null!==(n=e.dataset.width)&&void 0!==n?n:null,this.position=null!==(s=e.dataset.pos)&&void 0!==s?s:"right",this.noClick=void 0!==e.dataset.noclick,this.waitingForClose=!1,this.initEvents()}initEvents(){"1"!==this.element.dataset.acTooltipInit&&(this.container=V(),this.width&&(this.container.style.width=`${this.width}px`),this.attachModel(),this.element.dataset.acTooltipInit="1",this.element.addEventListener("mouseenter",(()=>{this.onScreen||(this.onScreen=!0,document.body.appendChild(this.container),this.container.style.width=`${this.width}px`,this.setPosition(),setTimeout((()=>{this.setPosition()}),100))})),this.element.addEventListener("mouseleave",(()=>{this.checkClose()})),this.element.addEventListener("click",(()=>{this.noClick||(this.waitingForClose=!0)})))}setPosition(){const e=document.body.getBoundingClientRect(),t=this.element.getBoundingClientRect();switch(this.container.style.top=t.top-e.top+this.element.offsetHeight-this.container.offsetHeight/2+20+"px",this.position){case"right_bottom":this.container.style.top=t.top-e.top-this.container.offsetHeight+100+"px",this.container.style.left=t.left-e.left+this.element.offsetWidth+14+"px";break;case"left":this.container.style.left=t.left-e.left-this.container.offsetWidth-12+"px";break;default:this.container.style.left=t.left-e.left+this.element.offsetWidth+10+"px"}}checkClose(){setTimeout((()=>{this.waitingForClose||this.closeHandler()}),50)}closeHandler(){this.onScreen&&(document.body.removeChild(this.container),this.onScreen=!1,this.waitingForClose=!1)}destroyComponent(){null!==this.component&&this.component.$destroy()}attachModel(){this.component=new J({target:this.container,props:{content:this.target.innerHTML,position:this.position,closeHandler:()=>this.closeHandler(),destroyHandler:()=>this.destroyComponent()}})}}const V=()=>{const e=document.createElement("div");return e.classList.add("ac-pointer-modal-container"),e.style.position="absolute",e};var G=n(559),z=n.n(G);class Q{constructor(e){this.element=e}static find(e){let t=document.querySelector(e);return null===t?null:new Q(t)}static create(e){return new Q(document.createElement(e))}getElement(){return this.element}addId(e){return this.element.id=e,this}toggleClass(e,t=null){return null===t?this.element.classList.contains(e)?this.removeClass(e):this.addClass(e):t?this.addClass(e):this.removeClasses(e)}addClass(e){return this.element.classList.add(e),this}addClasses(...e){return e.forEach((e=>this.addClass(e))),this}removeClasses(...e){return e.forEach((e=>this.removeClass(e))),this}removeClass(e){return this.element.classList.remove(e),this}setAttribute(e,t){return this.element.setAttribute(e,t),this}setAttributes(e){return Object.keys(e).forEach((t=>this.setAttribute(t,e[t]))),this}addHtml(e){return this.element.innerHTML=e,this}append(e){return this.element.appendChild(e),this}appendSelfTo(e){return e.append(this.element),this}prepend(e){return this.element.prepend(e),this}prependSelfTo(e){return e.prepend(this.element),this}css(e,t){return this.element.style[e]=t,this}insertAfter(e){var t;try{null===(t=this.element.parentElement)||void 0===t||t.insertBefore(e,this.element.nextElementSibling)}catch(e){console.error("Not able to insert element after current node",this.element)}}insertSelfBefore(e){var t;try{null===(t=e.parentElement)||void 0===t||t.insertBefore(this.element,e)}catch(e){console.error("Not able to insert element before current node",this.element)}return this}insertBefore(e){var t;try{null===(t=this.element.parentElement)||void 0===t||t.insertBefore(e,this.element)}catch(e){console.error("Not able to insert element before current node",this.element)}return this}addEventListener(e,t){return this.element.addEventListener(e,t),this}addEventListeners(e,t){return e.forEach((e=>this.addEventListener(e,t))),this}}class X{constructor(){this.filters={}}addFilter(e,t,n=10){this.filters.hasOwnProperty(e)||(this.filters[e]={}),this.filters[e].hasOwnProperty(n)||(this.filters[e][n]=[]),this.filters[e][n].push(t)}applyFilters(e,t,n={}){return this.filters.hasOwnProperty(e)?(Object.keys(this.filters[e]).forEach((s=>{this.filters[e][parseInt(s)].forEach((e=>{t=e(t,n)}))})),t):t}}function Z(e,t,n){const s=e.slice();return s[5]=t[n],s}function Y(e){let t;return{c(){t=w("span"),C(t,"class","dashicons dashicons-yes")},m(e,n){g(e,t,n)},d(e){e&&b(t)}}}function ee(e){let t,n,s,i,o,r,l,a=e[5].label+"",c=e[0]===e[5].value&&Y();function u(){return e[4](e[5])}return{c(){t=w("button"),c&&c.c(),n=$(),s=_(a),i=$(),C(t,"class","button"),t.value=o=e[5].value,L(t,"button-primary",e[0]===e[5].value)},m(e,o){var a;g(e,t,o),c&&c.m(t,null),y(t,n),y(t,s),y(t,i),r||(l=E(t,"click",(a=u,function(e){return e.preventDefault(),a.call(this,e)})),r=!0)},p(i,r){var l,u;(e=i)[0]===e[5].value?c||(c=Y(),c.c(),c.m(t,n)):c&&(c.d(1),c=null),2&r&&a!==(a=e[5].label+"")&&(u=""+(u=a),(l=s).data!==u&&(l.data=u)),2&r&&o!==(o=e[5].value)&&(t.value=o),3&r&&L(t,"button-primary",e[0]===e[5].value)},d(e){e&&b(t),c&&c.d(),r=!1,l()}}}function te(e){let t,n=e[1],s=[];for(let t=0;t<n.length;t+=1)s[t]=ee(Z(e,n,t));return{c(){t=w("div");for(let e=0;e<s.length;e+=1)s[e].c();C(t,"class","acui-toggle-buttons")},m(e,n){g(e,t,n);for(let e=0;e<s.length;e+=1)s[e]&&s[e].m(t,null)},p(e,[i]){if(7&i){let o;for(n=e[1],o=0;o<n.length;o+=1){const r=Z(e,n,o);s[o]?s[o].p(r,i):(s[o]=ee(r),s[o].c(),s[o].m(t,null))}for(;o<s.length;o+=1)s[o].d(1);s.length=n.length}},i:r,o:r,d(e){e&&b(t),function(e,t){for(let n=0;n<e.length;n+=1)e[n]&&e[n].d(t)}(s,e)}}}function ne(e,t,n){let{value:s}=t,{options:i}=t,{onChange:o}=t;const r=e=>{n(0,s=e),o(e)};return e.$$set=e=>{"value"in e&&n(0,s=e.value),"options"in e&&n(1,i=e.options),"onChange"in e&&n(3,o=e.onChange)},[s,i,r,o,e=>r(e.value)]}const se=class extends W{constructor(e){super(),F(this,e,ne,te,h,{value:0,options:1,onChange:3})}};class ie{constructor(e){this.input=e,this.parseOptions(),this.wrap()}parseOptions(){const e=this.input.dataset.options?JSON.parse(this.input.dataset.options):{};let t=[];Object.keys(JSON.parse(this.input.dataset.options)).forEach((n=>{t.push({value:n,label:e[n]})})),this.options=t}wrap(){const e=Q.create("div").addClass("acui-plugin-toggle-buttons"),t=Q.create("div");this.input.readOnly=!0,this.input.type="hidden",this.input.parentElement.insertBefore(e.getElement(),this.input),e.append(this.input).append(t.getElement()),new se({target:t.getElement(),props:{onChange:e=>{this.input.value=e,this.input.dispatchEvent(new Event("change")),this.input.dispatchEvent(new Event("input"))},value:this.input.value,options:this.options}})}}window.AC_SERVICES||(window.AC_SERVICES=new class{constructor(){this.services={},this.events=new(z()),this.filters=new X,this.$=Q}registerService(e,t){return this.services[e]=t,this.events.emit(`Service.Registered.${e}`,t),this}getService(e){return this.hasService(e)?this.services[e]:null}hasService(e){return this.services.hasOwnProperty(e)}addListener(e,t){this.events.addListener(e,t)}emitEvent(e,t){this.events.emit(e,t)}}),window.AC_SERVICES,document.addEventListener("DOMContentLoaded",(()=>{document.querySelectorAll(".ac-section").forEach((e=>{new t(e)})),jQuery(document).on("select2:open",(()=>{var e;null===(e=document.querySelector(".select2-container--open .select2-search__field"))||void 0===e||e.focus()})),((e=null)=>{e||(e=document.querySelectorAll(".ac-pointer")),e.forEach((e=>{var t;let n=document.querySelector(null!==(t=`#${e.getAttribute("rel")}`)&&void 0!==t?t:"#n");n&&new K(e,n)})),new o})(),document.querySelectorAll('input[data-component="acui-toggle-buttons"]').forEach((e=>{new ie(e)}))}))})()})();