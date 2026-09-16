import {beforeEach,describe,it,expect,vi} from 'vitest'
import {createPinia,setActivePinia} from 'pinia'
import {useCareStore} from '@/state/care.js'
import {careApi} from '@/services/care.js'
vi.mock('@/services/care.js',()=>({careApi:{recipients:vi.fn(),recipient:vi.fn(),entries:vi.fn(),notifications:vi.fn(),read:vi.fn(),saveEntry:vi.fn()}}))
beforeEach(()=>{setActivePinia(createPinia());vi.clearAllMocks()})
describe('estado dos cuidados',()=>{
 it('descarta a resposta do grupo anterior após limpar o contexto',async()=>{
  let finish;careApi.recipients.mockReturnValue(new Promise(resolve=>{finish=resolve}));const store=useCareStore();const pending=store.loadRecipients();store.clear();finish({data:[{id:99,name:'Outro grupo'}]});await pending;expect(store.recipients).toEqual([])
 })
 it('mantém os assistidos disponíveis entre consumidores do mesmo Pinia',async()=>{
  careApi.recipients.mockResolvedValue({data:[{id:1,name:'Luna'}]});const first=useCareStore();await first.loadRecipients();expect(useCareStore().recipients[0].name).toBe('Luna')
 })
 it('exibe erro de validação e encerra o carregamento',async()=>{
  careApi.saveEntry.mockRejectedValue({response:{data:{errors:{shares:['O rateio não corresponde ao total.']}}}});const store=useCareStore();await expect(store.saveEntry(1,{kind:'expense'})).rejects.toBeDefined();expect(store.error).toContain('rateio');expect(store.pending).toBe(0)
 })
 it('só marca a notificação como lida após resposta do servidor',async()=>{
  const store=useCareStore();store.notifications=[{id:1,read_at:null}];careApi.read.mockRejectedValue(new Error('offline'));await expect(store.read(1)).rejects.toThrow();expect(store.notifications[0].read_at).toBeNull();careApi.read.mockResolvedValue({});await store.read(1);expect(store.notifications[0].read_at).toBeTruthy()
 })
 it('não solicita documentos nem acessos sem a capacidade correspondente',async()=>{
  const store=useCareStore();careApi.recipient.mockResolvedValue({data:{id:1,capabilities:{documents:{view:false}},can_manage_access:false}});careApi.entries.mockResolvedValue({data:[]});await store.loadRecipient(1);expect(store.recipient.id).toBe(1);expect(store.documents).toEqual([]);expect(store.accesses).toEqual([])
 })
})

it('não recarrega registros do grupo antigo após uma gravação atrasada',async()=>{
 let finish;careApi.saveEntry.mockReturnValue(new Promise(resolve=>{finish=resolve}));const store=useCareStore();const request=store.saveEntry(99,{kind:'task'});store.clear();finish({data:{}});await request;expect(careApi.entries).not.toHaveBeenCalled();expect(store.entries).toEqual([])
});
it('ignora erros atrasados do grupo anterior',async()=>{
 let fail;careApi.recipients.mockReturnValue(new Promise((resolve,reject)=>{fail=reject}));const store=useCareStore();const request=store.loadRecipients();store.clear();fail(new Error('offline'));await expect(request).rejects.toThrow();expect(store.error).toBe('');expect(store.pending).toBe(0)
});
