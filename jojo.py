# -*- encoding: utf-8 -*-

import mechanize
from bs4 import BeautifulSoup
import re

class JOJO():
    
    def __init__(self, url):
        self.url = url;
        
    def scrape(self):
        if self.url == 'http://matome.naver.jp/odai/2134190381401872101':
            return self.scpare_from_naver()
        # you can write other cases
        #elif:
        else:
            return self.scpare_from_naver()
            
    def scpare_from_naver(self):
        PAGINATTION_CLASS = 'MdPagination03'
        WORD_CLASS = 'mdMTMWidget01ItemTtl01View'
        ret = list()
        br = mechanize.Browser()
        
        br.set_handle_robots(False)
        br.open(self.url)
        html = BeautifulSoup(br.response().read(), 'html.parser')
        pages = [content.text for content in html.find('div',class_=PAGINATTION_CLASS).findAll(['a', 'strong'])]
        for page in pages:
            url = self.url + '?page=' + str(page)
            br.open(url)
            html = BeautifulSoup(br.response().read(), 'html.parser')
            elms = html.findAll(class_=WORD_CLASS)
            for elm in elms:
                if len(elm.text) == 0 or elm.find('a') is not None or True in map(lambda t: t in elm.text, ['▼', '■']):
                    continue
                ret.append('"' + elm.text.replace('「', '').replace('」', '').replace('』', '').replace('『', '') + '",')
        return ret
                
        
j = JOJO('http://matome.naver.jp/odai/2134190381401872101')
for t in j.scrape():
    print t 
