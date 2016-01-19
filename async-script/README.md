如何让页面尽可能早地渲染页面，页面更早可见，让白屏时间更短，一直是 web 性能优化的话题。
本篇结合[@冬萌的测试](http://www.atatech.org/articles/41858)，继续测试如何改进页面可见时间。

## 页面可见时间
页面可见要经历以下过程：


* 解析HTML为DOM，解析 CSS 为 CSSOM（CSS Object Model）
* 将 DOM 和 CSSOM 合成一棵渲染树（[render tree](https://developers.google.com/web/fundamentals/performance/critical-rendering-path/render-tree-construction?hl=zh-cn)）
* 完成渲染树的布局（layout）
* 将渲染树绘制到屏幕


![](http://img.alicdn.com/tps/TB1U89HLpXXXXaxXXXXXXXXXXXX-759-352.jpg)

layout


![](http://img.alicdn.com/tps/TB1nZyyLpXXXXbCXpXXXXXXXXXX-610-274.jpg)

由于 js 可能随时会改变`DOM`和`CSSOM`，当页面中有大量的 js 想立刻执行时，浏览器下载并执行，直到完成 CSSOM 下载与构建，而在我们等待时，DOM 构建同样被阻塞。为了 js 不阻塞 DOM 和 CSSDOM 的构建，不影响首屏可见的时间，测试几种js加载策略对页面可见的影响：


## 几种异步加载方式测试


* A. head script: 即普通的将 js 放在 `head` 中或放在 `body` 中间；[demo地址](./head-script.html)
* B. bottom script: 即常规的优化策略，js 放`body`的底部；[demo地址](./bottom-script.html)
* C. document.write: 以前 PC 优化少用的一种异步加载 js 的策略；[demo地址](./script-document-write.html)

  ```
    function injectWrite(src){
      document.write('<script src="' + src + '"></sc' + 'ript>');
    }
  ```


* D. getScript: 形如以下，也是KISSY内部的`getScript`函数的简易实现；[demo地址](./script-inject.html)

  ```
  <script>
    var script = document.createElement('script');
    script.src = "//g.tbcdn.com/xx.js";
    document.getElementsByTagName('head')[0].appendChild(script);
  </script>
  ```

* E. 加 `async` 属性 [demo地址](./script-async.html)
* F. 加 `defer` 属性 [demo地址](./script-defer.html)
* G. 同时加 `async` `defer` 属性[demo地址](./script-async-defer.html)

### 测试结果

以下提到的 `domready` 同 `DOMContentLoaded` 事件。

||  | A (head script) | B (bottom script) | C (document.write) | D (getScript) | E (async) | F (defer) | G (async + defer) |
|-------------|-------------|----------|-------------|----------|----------|-------------|----------|----------|
| 1 | pc chrome | 页面白屏长、domready:5902.545、onload:5931.48 | 页面先显示、domready:5805.21、onload:5838.255 | 页面先显示、domready:5917.95、onload:5949.30 |页面先显示、domready:244.41、onload:5857.645| 页面先显示、domready:567.01、onload:5709.33|页面先显示、domready:5812.12、onload:5845.6|页面先显示、domready:576.12、onload:5743.79|
|2| ios safari |页面白屏长、domready:6130、onload:6268.41|页面白屏长、domready:5175.80、onload:5182.75|页面白屏长、domready:5617.645、onload:5622.115|502s白屏然后页面显示最后变更load finish时间、domready:502.71、onload:6032.95|508s白屏然后页面显示最后变更load finish time domready:508.95、onload:5538.135|页面白屏长、domready:5178.98、onload:5193.58|556s白屏然后页面显示最后变更load finish时间、domready:556、onload:5171.95|
|3|  ios 手淘 webview |页面白屏长、页面出现loading消失、domready: 5291.29、onload:5292.78|页面白屏长、页面未跳转loading消失、domready: 5123.46、onload:5127.85|页面白屏长、页面未跳转loading消失、domready: 5074.86、onload:5079.875|页面可见快、loading消失快在domready稍后、domready:14.06、load finish:5141.735 |页面可见快、loading消失快在domready稍后、domready:13.89、load finish:5157.15|页面白屏长、loading先消失再出现页面、domready: 5132.395、onload:5137.52|页面可见快、然后loading消失、domready:13.49、load finish:5124.08|
|4|  android browser |页面白屏长、domready: 5097.29、onload:5100.37|页面白屏长、domready: 5177.48、onload:5193.66|页面白屏长、domready: 5125.96、onload:5165.06|页面可见快、等5s后更新load finish时间 domready:463.33、load finish:5092.90|页面可见快、等5s后更新load finish时间 domready:39.34、load finish:5136.55|页面白屏长、domready: 5092.45、onload:5119.81|页面可见快、等5s后更新load finish时间 domready:50.49、load finish:5507.668|
|5| android 手淘 webview |白屏时间长、一直loading直接页面可见、domready:5058.91、onload:5073.81|页面立即可见、loading消失快、等5s后更新domready时间和load时间 domready:4176.34、onload:4209.50|页面立即可见、loading消失快、domready:6011.18、onload:6031.93|页面可见快、loading之后消失、等5s后更新load finish时间 domready:36.31、load finish:5081.76|页面可见快、loading随后消失、等5s后更新load finish时间 domready:25.11、load finish:5113.81|页面可见快、loading随后消失、等5s后更新domready时间和load时间  domready:5213.11、load finish:5312.19|页面可见快、loading随后消失、等5s后更新load finish时间 domready:89.67、load finish:5589.95|

从以上测试结果可以看出以下结论：

* 横向看， ios safari 和 android browser 的在页面可见、domready、onload的时间表现一致。
* 纵向看，bottom script、document.write 和 defer 三列，可知 document.write 和 defer 无任何异步效果，可见时间、domready、onload的触发时间和 bottom script 的情况一致。
* 纵向看，async + defer 联合用和 async 的表现一致，故合并为async。
* 纵向看，script 放页头（head script）和 script 放 body 底部（bottom script）。ios safari 、android browser 和 ios webview 表现一致，即使 script 放在 body 的底部也无济于事，页面白屏时间长，要等到 domready 5s多后结束才显示页面；唯独 android webview 的表现和 pc 的 chrome 一致。
* 单纯看手淘 webview 容器中 loading 消失的时间，这个时间点ios 和 android 的表现一致，即都是在 UIwebview 的 didFinishLoad 事件触发时消失。这个事件的触发可能在 domready 之前（如：A3、B3），也可能在 domready 之后（如：D3、E3）；这个事件触发和 js 中的 onload 触发时机也没有必然的联系，可能在 onload 之前（如：D3、E3）也可能在 onload 几乎同时(如：A5)。 didiFinishLoad 到底是什么时机触发的呢，详见下章。
* 页面可见时间，getScript 方式和 async 方式页面可见都非常快，domready 的时间触发得也非常快，客户端的 loading 在 domready 稍后即消失。原因是因为[最后耗时的js请求异步化了](https://developers.google.com/web/fundamentals/performance/critical-rendering-path/adding-interactivity-with-javascript?hl=zh-cn)，没有阻塞浏览器的 DOM + CSSOM构建，页面渲染完成就立刻可见了。整体看，如果domready的时间快，则页面可见快；反之如果页面可见快，domready的时间不一定快，如B5、B1、C1、C5、F1、F5。如果异步化耗时长的js，domready和onload的时间差距是很大的，不做任何处理 onload 的时间 domready 的时间差 30ms 左右。所以在异步化的前提下，可以用 domready 的时间作为页面可见的时间。

## didFinishLoad 到底什么时候触发
didFinishLoad 是native定义的事件，该事件触发时手淘 loading 菊花消失，并且 winvane 中的发出请求不再收集，也就是 native 统计出的 pageLoad 时间。在[用户数据平台看到的瀑布流请求](http://har.fed.taobao.net/)，就是在 didFinishLoad 触发前收集到的所有请求。

![](https://img.alicdn.com/tps/TB10ruuLpXXXXaEXFXXXXXXXXXX-1853-779.jpg)

经过上方测试，客户端的 didFinisheLoad 事件的触发和js中的domready（DOMContentLoaded）和 onload 触发没有任何关联。可能在 domready 之前或之后，也可能在 onload 之前或之后。

那它到底是什么时候触发呢？ [ios官方文档](https://developer.apple.com/library/ios/documentation/UIKit/Reference/UIWebViewDelegate_Protocol/#//apple_ref/occ/intfm/UIWebViewDelegate/webViewDidFinishLoad:) 是 Sent after a web view finishes loading a frame。 结合收集的用户请求和测试，didFinishLoad是在连续发起的请求结束之后触发，监听一段时间内无请求则触发。

所以经常会看到data_sufei这个js，在有些用户的瀑布流里面有，在有些用户的又没有。原因是这个js是aplus_wap.js 故意 setTimeout 1s后发出的，如果页面在1s前所有的请求都发完了则触发didFinishLoad，后面的data_sufei.js的时间就不算到pageLoad的时间；反之如果接近1s页面还有图片等请求还在发，则data_sufei.js的时间也会被算到里面。

因此在js中用 setTimeout 来延迟发送请求也有可能会影响 didFinishLoad 的时间，建议 setTimeout的时间设置得更长一点，如3s。


## async 和 defer

script 标签上可以添加 defer 和 async 属性来优化此 script 的下载和执行。

### defer ：延迟
HTML 4.0 规范，其作用是，告诉浏览器，等到DOM+CSSOM渲染完成，再执行指定脚本。

```
  <script defer src="xx.js"></script>
```

> * 浏览器开始解析HTML网页
> * 解析过程中，发现带有defer属性的script标签
> * 浏览器继续往下解析HTML网页，解析完就渲染到页面上，同时并行下载script标签中的外部脚本
> * 浏览器完成解析HTML网页，此时再执行下载的脚本，完成后触发 DOMContentLoaded

下载的脚本文件在 DOMContentLoaded 事件触发前执行（即刚刚读取完\<\/html>标签），而且可以保证执行顺序就是它们在页面上出现的顺序。所以 添加 defer 属性后，domready的时间并没有提前，但它可以让页面更快显示出来。

将放在页面上方的 script 加defer，在 PC chrome 下其效果相当于 把这个 script 放在底部，页面会先显示。 但对ios safari 和 ios webview 加defer 和 script 放底部一样都是长时间白屏。

### async: 异步

HTML5 规范，其作用是，使用另一个进程下载脚本，下载时不会阻塞渲染，并且下载完成后立刻执行。

```
  <script async src="yy.js"></script>
```

> * 浏览器开始解析HTML网页
> * 解析过程中，发现带有async属性的script标签
> * 浏览器继续往下解析HTML网页，解析完先显示页面并触发DOMContentLoaded，同时并行下载script标签中的外部脚本
> * 脚本下载完成，浏览器暂停解析HTML网页，开始执行下载的脚本
> * 脚本执行完毕，浏览器恢复解析HTML网页

async属性可以保证脚本下载的同时，浏览器继续渲染。但是 async 无法保证脚本的执行顺序。哪个脚本先下载结束，就先执行那个脚本。

### 如何选择 async 和 defer

* `defer`可以保证执行顺序，`async`不行【注：<=ie9 defer执行顺序有bug，但可以[hack](https://github.com/h5bp/lazyweb-requests/issues/42)】
* `async`可以提前触发`domready`，`defer`不行【注：firefox的`defer`也可以提前触发`domready`】
* `defer` 在 ios 和部分 android 下依然阻塞渲染，白屏时间长。
* 当 script 同时加 `async` 和 `defer` 属性时，后者不起作用，浏览器行为由`async`属性决定。
* `async` 和 `defer` 的兼容性不一致，好在 `async`和`defer` 无线端基本都支持，`async`不支持ie9-。 
附 [async 兼容性](http://caniuse.com/#search=async) [defer 兼容性](http://caniuse.com/#search=defer)


## script inject 和 async

```
    <!-- BAD -->
  <script src="//g.alicdn.com/large.js"></script>
  
  <!-- GOOD -->
  <script>
    var script = document.createElement('script');
    script.src = "//g.alicdn.com/large.js";
    document.getElementsByTagName('head')[0].appendChild(script);
  </script>
```

我们通常用这种 inject script 的方式来异步加载文件，特别是以前`seajs`、`KISSY`的盛行时，出现大量使用`$.use`来加载页面入口文件。这种方式和`async`的一样都能异步化 js，不阻塞页面渲染。但真的是最快的吗？


一个常见的页面如下：一个 css , 2个异步的 js

js 使用 script inject 的方式测试结果如下，[demo]()：


![](http://img.alicdn.com/tps/TB1e4yFLpXXXXcHXXXXXXXXXXXX-1395-224.jpg)

js 使用 async 的方式测试结果如下， [demo]()：

![](http://img.alicdn.com/tps/TB1bIilLpXXXXX2XVXXXXXXXXXX-1394-225.jpg)


对比结果发现，通过 `<script async>` 的方式的 js 可以和 css 并发下载，这样整个页面load时间变得更短，js更快执行完，这样页面的交互或数据等可以更快更新。为什么呢？因为浏览器有类似‘[preload scanner]()’的功能，在 parse html 时就可以提前并发去下载js文件，如果把js的文件隐藏在js逻辑中，浏览器就没这么智能发现了。

综合上面 async 和 defer，推荐以下用法。


```
  <!-- 现代浏览器用 'async', ie9-用 'defer' -->
  <script src="//g.alicdn.com/alilog/mlog/aplus_wap.js" async defer></script>
```

其实现在无线站点 aplus.js 可以完全用这种方式引入，既不会阻塞 `DOM` 和`CSSOM`，也不会延长整个页面 `onload` 时间，而不是原来的 pc 上的`script inject`方式。

如果 aplus.js 在 pc 上这么用，ie8/ie9 应用的是 defer 属性，不会阻塞页面渲染，但是这个js需要执行完后才触发domready（DOMContentLoaded）事件，故在ie8/ie9 下可能会影响 domready 的时间。

## 最后建议

* 业务 js 尽量异步，放body底部的js在 ios 上和部分 android 是无效的，依然会阻塞首屏渲染。
* 异步的方式尽可能原生用`async`，容器（浏览器、webview等）级别自带优化，不要通过 js 去模拟实现，如 getScript/ajax/Kissy.use/$.use 等。
* 有顺序依赖关系的js可以加 defer，不改变执行顺序，相当于放到页面底部，如 tms head 中一时无法挪动位置的类库等。


action:

* 由于kissy的传承关系，kimi 的业务都是通过 $.use 去加载页面脚本并初始化的，cake 中改为直接使用原生的 script 标签并加 async。


## 参考资料

* http://javascript.ruanyifeng.com/bom/engine.html#toc5

* http://www.stevesouders.com/blog/2013/11/16/async-ads-with-html-imports/

* https://www.igvita.com/2014/05/20/script-injected-async-scripts-considered-harmful/

* https://developers.google.com/web/fundamentals/performance/critical-rendering-path/render-tree-construction?hl=zh-cn



