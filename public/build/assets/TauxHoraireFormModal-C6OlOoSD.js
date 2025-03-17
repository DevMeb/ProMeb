import{p as C,e as _,x as y,q as g,s as M,C as q,c as v,a as h,b as s,w as T,g as E,t as H,k as V,f as j,v as A,n as B,u}from"./app-Jvx3VFZV.js";const D=C("taux-horaires",()=>{const r=_([]),i=_({}),x=_({});function b(e){e?i.value[e]=null:i.value={}}function f(e,a){x.value[e]=a}async function l({operation:e,request:a,onSuccess:t}){var c,n,m;b(e),f(e,!0);try{const o=await a();return t&&t(o),o}catch(o){((c=o.response)==null?void 0:c.status)===422?i.value.validationErrors=o.response.data.errors:(i.value[e]=((m=(n=o.response)==null?void 0:n.data)==null?void 0:m.message)||"Une erreur est survenue.",y("error",i.value[e]))}finally{f(e,!1)}}async function w(){return l({operation:"fetch",request:()=>g.get("/api/taux-horaires"),onSuccess:e=>{r.value=e.data.taux_horaires}})}async function k(e){return l({operation:"add",request:()=>g.post("/api/taux-horaires",e),onSuccess:a=>{r.value.push(a.data.taux_horaire),y("success",a.data.message)}})}async function p(e){return l({operation:"update",request:()=>g.put(`/api/taux-horaires/${e.id}`,e),onSuccess:a=>{const t=r.value.findIndex(c=>c.id===e.id);t!==-1&&(r.value[t]=a.data.taux_horaire),y("success",a.data.message)}})}async function d(e){return l({operation:"delete",request:()=>g.delete(`/api/taux-horaires/${e}`),onSuccess:a=>{r.value=r.value.filter(t=>t.id!==e),y("success",a.data.message)}})}return{tauxHoraires:r,errors:i,loading:x,fetchTauxHoraires:w,addTauxHoraire:k,updateTauxHoraire:p,deleteTauxHoraire:d,clearErrors:b}}),N={class:"fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-md z-50"},z={class:"bg-white p-6 rounded-xl shadow-xl w-[90%] sm:w-96 transform transition-all animate-fade-in"},F={class:"flex items-center justify-between border-b pb-2"},U={class:"text-xl font-semibold text-gray-800 flex items-center"},$={key:0,class:"mr-2"},I={key:1,class:"mr-2"},L={key:0,class:"text-red-500 text-xs mt-1"},O={class:"flex justify-end space-x-3 mt-4"},R=["disabled"],G={key:0,class:"animate-spin mr-2"},K={__name:"TauxHoraireFormModal",props:{tauxHoraire:Object},emits:["close"],setup(r,{emit:i}){const x=r,b=i,f=D(),{addTauxHoraire:l,updateTauxHoraire:w,clearErrors:k}=f,{errors:p,loading:d}=M(f),e=_({id:null,taux:""});q(()=>{e.value=x.tauxHoraire?{...x.tauxHoraire}:{id:null,taux:""}});const a=async()=>{(e.value.id?await w(e.value):await l(e.value))&&t()},t=()=>{k("validationErrors"),b("close")};return(c,n)=>{var m,o;return h(),v("div",N,[s("div",{onClick:T(t,["self"]),class:"fixed inset-0"}),s("div",z,[s("div",F,[s("h2",U,[e.value.id?(h(),v("span",$,"✏️")):(h(),v("span",I,"➕")),E(" "+H(e.value.id?"Modifier le taux horaire":"Ajouter un taux horaire"),1)]),s("button",{onClick:t,class:"text-gray-500 hover:text-gray-700 transition"},"✖️")]),s("form",{onSubmit:T(a,["prevent"]),class:"mt-4 space-y-4"},[s("div",null,[n[1]||(n[1]=s("label",{for:"taux",class:"block text-sm font-medium text-gray-700"},"Taux Horaire (€ / h) *",-1)),V(s("input",{ref:"firstInput",type:"number",id:"taux",step:"0.01","onUpdate:modelValue":n[0]||(n[0]=S=>e.value.taux=S),class:B(["w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500",{"border-red-500":(m=u(p).validationErrors)==null?void 0:m.taux}])},null,2),[[A,e.value.taux]]),(o=u(p).validationErrors)!=null&&o.taux?(h(),v("p",L,H(u(p).validationErrors.taux.join(", ")),1)):j("",!0)]),s("div",O,[s("button",{type:"button",onClick:t,class:"px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 transition"}," Annuler "),s("button",{type:"submit",class:"px-4 py-2 bg-indigo-600 text-white rounded flex items-center hover:bg-indigo-700 transition",disabled:e.value.id?u(d).update:u(d).add},[(e.value.id?u(d).update:u(d).add)?(h(),v("span",G,"⏳")):j("",!0),E(" "+H(e.value.id?"Mettre à jour":"Ajouter"),1)],8,R)])],32)])])}}};export{K as _,D as u};
