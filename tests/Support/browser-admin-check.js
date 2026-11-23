async page => {
 const check = (condition, message) => { if(!condition) throw new Error(message); };
 check((await page.locator('main').innerText()).includes('8:00 AM'), 'admin WIB');
 await page.getByRole('button',{name:'Setujui',exact:true}).click();
 await page.getByRole('button',{name:'Konfirmasi Setujui',exact:true}).click();
 await page.waitForLoadState('domcontentloaded');
 check((await page.locator('main').innerText()).includes('Approved'), 'approval saved');
 await page.screenshot({path:'D:/Documents/Artifact Documentation Project/Room Sense/v11-browser-approved.png',fullPage:true});
 await page.goto('http://127.0.0.1:8093/book');
 const room = await page.locator('#room_id option').filter({hasText:'WIB Browser Room'}).getAttribute('value');
 const min = await page.locator('#date').getAttribute('min');
 const date = new Date(Date.parse(min+'T12:00:00Z')+86400000).toISOString().slice(0,10);
 await page.locator('#room_id').selectOption(room);
 await page.locator('#date').fill(date);
 await page.locator('#date').dispatchEvent('change');
 await page.waitForFunction(() => document.querySelector('#schedule-card') && !document.querySelector('#schedule-card').classList.contains('hidden'));
 const expectedDate = new Date(date+'T00:00:00+07:00').toLocaleDateString('id-ID',{weekday:'long',year:'numeric',month:'long',day:'numeric',timeZone:'Asia/Jakarta'});
 check(await page.locator('#schedule-date').innerText() === expectedDate, 'WIB date heading');
 const panel = await page.locator('#schedule-card').innerText();
 check(panel.includes('08:00') && panel.includes('10:00') && panel.includes('Approved'), 'reactive WIB schedule');
 await page.screenshot({path:'D:/Documents/Artifact Documentation Project/Room Sense/v11-browser-schedule.png',fullPage:true});
 await page.setViewportSize({width:390,height:844});
 await page.screenshot({path:'D:/Documents/Artifact Documentation Project/Room Sense/v11-browser-mobile.png',fullPage:true});
 return {status:'PASS',checks:['Admin approval persisted and displays 08:00–10:00 WIB','Reactive room schedule renders approved 08:00–10:00','Mobile form 390x844 inspected'],date,room};
}
