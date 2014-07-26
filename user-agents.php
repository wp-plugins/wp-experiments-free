<?php 

$_ROBOT_USER_AGENTS = array (
	// note that this is meant to be used in a case-insensitive setup

	/**** THE BIG THREE ********/
	'googlebot\/',          /* Google see http://www.google.com/bot.html              */
	'Googlebot-Mobile',
	'Googlebot-Image',
	'bingbot',            /* Microsoft Bing, see http://www.bing.com/bingbot.htm   */
	'slurp',              /* Yahoo, see http://help.yahoo.com/help/us/ysearch/slurp */

	/**** Home grown ********/
	'java', 
	'wget',
	'curl',
	'Commons-HttpClient',
	'Python-urllib',
	'libwww',
	'httpunit',
	'nutch',
	'phpcrawl',           /* added 2012-09/17, see http://phpcrawl.cuab.de/ */

	/** The others */
	'msnbot',             /* see http://search.msn.com/msnbot.htm   */
	'Adidxbot',           /* see http://onlinehelp.microsoft.com/en-us/bing/hh204496.aspx */
	'blekkobot',          /* see http://blekko.com/about/blekkobot */
	'teoma', 
	'ia_archiver',
	'GingerCrawler',
	'webmon ',            /* the space is required so as not to match webmoney */
	'httrack',
	'webcrawler',
	'FAST-WebCrawler',
	'FAST Enterprise Crawler',
	'convera',
	'biglotron',
	'grub.org',
	'UsineNouvelleCrawler',
	'antibot',
	'netresearchserver',
	'speedy',
	'fluffy',
	'jyxobot',
	'bibnum.bnf',
	'findlink',
	'exabot',
	'gigabot',
	'msrbot',
	'seekbot',
	'ngbot',
	'panscient',
	'yacybot',
	'AISearchBot',
	'IOI',
	'ips-agent',
	'tagoobot',
	'MJ12bot',
	'dotbot',
	'woriobot',
	'yanga',
	'buzzbot',
	'mlbot',
	'yandex', 
	'purebot',            /* added 2010/01/19  */
	'Linguee Bot',        /* added 2010/01/26, see http://www.linguee.com/bot */
	'Voyager',            /* added 2010/02/01, see http://www.kosmix.com/crawler.html */
	'CyberPatrol',        /* added 2010/02/11, see http://www.cyberpatrol.com/cyberpatrolcrawler.asp */
	'voilabot',           /* added 2010/05/18 */
	'baiduspider',        /* added 2010/07/15, see http://www.baidu.jp/spider/ */
	'citeseerxbot',       /* added 2010/07/17 */
	'spbot',              /* added 2010/07/31, see http://www.seoprofiler.com/bot */
	'twengabot',          /* added 2010/08/03, see http://www.twenga.com/bot.html */
	'postrank',           /* added 2010/08/03, see http://www.postrank.com */
	'turnitinbot',        /* added 2010/09/26, see http://www.turnitin.com */
	'scribdbot',          /* added 2010/09/28, see http://www.scribd.com */
	'page2rss',           /* added 2010/10/07, see http://www.page2rss.com */
	'sitebot',            /* added 2010/12/15, see http://www.sitebot.org */
	'linkdex',            /* added 2011/01/06, see http://www.linkdex.com */
	'ezooms',             /* added 2011/04/27, see http://www.phpbb.com/community/viewtopic.php?f=64&t=935605&start=450#p12948289 */
	'dotbot',             /* added 2011/04/27 */
	'mail\\.ru',          /* added 2011/04/27 */
	'discobot',           /* added 2011/05/03, see http://discoveryengine.com/discobot.html */
	'heritrix',           /* added 2011/06/21, see http://crawler.archive.org/ */
	'findthatfile',       /* added 2011/06/21, see http://www.findthatfile.com/ */
	'europarchive.org',   /* added 2011/06/21, see  http://www.europarchive.org/ */
	'NerdByNature.Bot',   /* added 2011/07/12, see http://www.nerdbynature.net/bot*/
	'sistrix crawler',    /* added 2011/08/02 */
	'ahrefsbot',          /* added 2011/08/28 */
	'Aboundex',           /* added 2011/09/28, see http://www.aboundex.com/crawler/ */
	'domaincrawler',      /* added 2011/10/21 */
	'wbsearchbot',        /* added 2011/12/21, see http://www.warebay.com/bot.html */
	'summify',            /* added 2012/01/04, see http://summify.com */
	'ccbot',              /* added 2012/02/05, see http://www.commoncrawl.org/bot.html */
	'edisterbot',         /* added 2012/02/25 */
	'seznambot',          /* added 2012/03/14 */
	'ec2linkfinder',      /* added 2012/03/22 */
	'gslfbot',            /* added 2012/04/03 */
	'aihitbot',           /* added 2012/04/16 */
	'intelium_bot',       /* added 2012/05/07 */
	'facebookexternalhit',/* added 2012/05/07 */
	'yeti',               /* added 2012/05/07 */
	'RetrevoPageAnalyzer',/* added 2012/05/07 */
	'lb-spider',          /* added 2012/05/07 */
	'sogou',              /* added 2012/05/13, see http://www.sogou.com/docs/help/webmasters.htm#07 */
	'lssbot',             /* added 2012/05/15 */ 
	'careerbot',          /* added 2012/05/23, see http://www.career-x.de/bot.html */
	'wotbox',             /* added 2012/06/12, see http://www.wotbox.com */
	'wocbot',             /* added 2012/07/25, see http://www.wocodi.com/crawler */
	'ichiro',             /* added 2012/08/28, see http://help.goo.ne.jp/help/article/1142 */
	'DuckDuckBot',        /* added 2012/09/19, see http://duckduckgo.com/duckduckbot.html */
	'lssrocketcrawler',   /* added 2012/09/24 */
	'drupact',            /* added 2012/09/27, see http://www.arocom.de/drupact */
	'webcompanycrawler',  /* added 2012/10/03 */
	'acoonbot',           /* added 2012/10/07, see http://www.acoon.de/robot.asp */  
	'openindexspider',    /* added 2012/10/26, see http://www.openindex.io/en/webmasters/spider.html */
	'gnam gnam spider',   /* added 2012/10/31 */
	'web-archive-net.com.bot', /* added 2012/11/12*/
	'backlinkcrawler',    /* added 2013/01/04 */
	'coccoc',             /* added 2013/01/04, see http://help.coccoc.vn/ */
	'integromedb',        /* added 2013/01/10, see http://www.integromedb.org/Crawler */
	'content crawler spider',/* added 2013/01/11 */
	'toplistbot',         /* added 2013/02/05 */
	'seokicks-robot',     /* added 2013/02/25 */
	'it2media-domain-crawler',      /* added 2013/03/12 */
	'ip-web-crawler.com', /* added 2013/03/22 */
	'siteexplorer.info',  /* added 2013/05/01 */
	'elisabot',           /* added 2013/06/27 */
	'proximic',           /* added 2013/09/12, see http://www.proximic.com/info/spider.php */
	'changedetection',    /* added 2013/09/13, see http://www.changedetection.com/bot.html */
	'blexbot',            /* added 2013/10/03, see http://webmeup-crawler.com/ */
	'arabot'              /* added 2013/10/09 */
);