async page => {
 await page.goto('http://127.0.0.1:8093/book');
 const room = await page.locator('#room_id option').filter({hasText:'WIB Browser Room'}).getAttribute('value');
 const min = await page.locator('#date').getAttribute('min');
 const date = new Date(Date.parse(min+'T12:00:00Z')+86400000).toISOString().slice(0,10);
 await page.locator('#room_id').selectOption(room);
 await page.locator('#date').fill(date);
 await page.locator('#date').dispatchEvent('change');
 await page.waitForFunction(() => !document.querySelector('#schedule-card').classList.contains('hidden'));
 const expected = new Date(date+'T00:00:00+07:00').toLocaleDateString('id-ID',{weekday:'long',year:'numeric',month:'long',day:'numeric',timeZone:'Asia/Jakarta'});
 const desktop = await page.locator('#schedule-date').innerText();
 const mobile = await page.locator('#bs-schedule-date').textContent();
 if(desktop!==expected || mobile!==expected) throw new Error('WIB date heading mismatch');
 await page.setViewportSize({width:1280,height:900});
 await page.screenshot({path:'D:/Documents/Artifact Documentation Project/Room Sense/v11-browser-schedule.png',fullPage:true});
 await page.setViewportSize({width:390,height:844});
 await page.screenshot({path:'D:/Documents/Artifact Documentation Project/Room Sense/v11-browser-mobile.png',fullPage:true});
 return {status:'PASS',selected:date,desktop,mobile,deviceTimezone:await page.evaluate(()=>Intl.DateTimeFormat().resolvedOptions().timeZone)};
}
