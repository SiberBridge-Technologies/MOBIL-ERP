import test from 'node:test';
import assert from 'node:assert/strict';
// @ts-ignore -- Node's type stripping requires explicit .ts extensions.
import { cartReducer, emptyCart, lineNet, total, validateCartItem } from '../services/cartLogic.ts';
// @ts-ignore -- executed directly by Node.
import { normalizeDate } from '../services/orderDate.ts';
const item={product_id:1,urun_kodu:'A',urun_adi:'A',koli_adedi:0.3,adet:3,koli_ici_adet:10,adet_fiyati:10,koli_fiyati:100,dip_fiyat:6,kdv_orani:20,stok:100,stand_aktif:0,stand_ici_adet:null,siparis_birimi:'ADET' as const,birim_miktari:3,iskonto_1:0,iskonto_2:0,iskonto_3:0};
test('unit quantity and tax match backend',()=>{assert.equal(lineNet(item),30);assert.equal(total([item]),36)});
test('discount rounding uses unit price before multiplication',()=>{assert.equal(lineNet({...item,adet:30,adet_fiyati:9.999,iskonto_1:3.33}),290.1)});
test('changing customer clears products and term atomically',()=>{const state={...emptyCart,customerId:1,customerName:'A',items:[item],vadeGun:30};const next=cartReducer(state,{type:'customer',id:2,name:'B'});assert.equal(next.items.length,0);assert.equal(next.vadeGun,0);assert.equal(next.customerId,2)});
test('same customer retains draft',()=>{const state={...emptyCart,customerId:1,items:[item]};assert.equal(cartReducer(state,{type:'customer',id:1,name:'A'}).items.length,1)});
test('clear removes customer and products',()=>assert.deepEqual(cartReducer({...emptyCart,customerId:1,items:[item]},{type:'clear'}),emptyCart));
test('cannot add invalid quantities, excess stock or dip price violations',()=>{assert.ok(validateCartItem({...item,adet:0.3}));assert.ok(validateCartItem({...item,adet:101}));assert.ok(validateCartItem({...item,iskonto_1:50}));assert.equal(validateCartItem(item),null)});
test('calendar validation includes leap days',()=>{assert.equal(normalizeDate('31.02.2027'),null);assert.equal(normalizeDate('29.02.2028'),'2028-02-29');assert.equal(normalizeDate('29.02.2027'),null)});
test('stand requires an active product and valid stand size',()=>{assert.ok(validateCartItem({...item,siparis_birimi:'STAND'}));assert.equal(validateCartItem({...item,siparis_birimi:'STAND',stand_aktif:1,stand_ici_adet:24,adet:24,birim_miktari:1}),null)});
