import{s as w,o as C,c as a,a as l,u as i,b as e,t as u,F as v,r as $,d as p,e as b,f as y,g as o,w as k,h as M}from"./app-Jvx3VFZV.js";import{u as _,_ as h}from"./ClientFormModal-CfEq8JED.js";/* empty css            */const j={class:"mt-6"},N={key:0,class:"flex justify-center my-6"},F={key:1,class:"flex justify-center my-6 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg"},S={class:"text-lg font-medium ml-2"},D={key:2,class:"flex justify-center my-6 bg-gray-800 px-6 py-4 rounded-lg shadow-lg"},B={key:3,class:"grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-8"},V={__name:"ClientList",setup(n){const r=_(),{fetchClients:d,clearErrors:s}=r,{clients:c,errors:f,loading:x}=w(r);return C(()=>{d(),s("fetch")}),(g,m)=>(l(),a("div",j,[i(x).fetch?(l(),a("div",N,m[0]||(m[0]=[e("div",{class:"animate-spin inline-block w-6 h-6 border-4 border-white border-t-transparent rounded-full"},null,-1),e("p",{class:"text-white text-lg font-medium ml-2"},"Chargement des clients...",-1)]))):i(f).fetch?(l(),a("div",F,[m[1]||(m[1]=e("span",{class:"text-xl"},"❌",-1)),e("p",S,u(i(f).fetch),1)])):i(c).length===0?(l(),a("div",D,m[2]||(m[2]=[e("p",{class:"text-gray-300 text-lg"},"📭 Aucun client trouvée.",-1)]))):(l(),a("div",B,[(l(!0),a(v,null,$(i(c),t=>(l(),p(i(P),{client:t,key:t.id},null,8,["client"]))),128))]))]))}},A={class:"bg-gray-800 p-6 rounded-lg shadow-lg flex flex-col space-y-4 hover:shadow-xl transition-all transform hover:scale-[1.02] border border-gray-700"},L={class:"flex justify-between items-center border-b pb-3"},q={class:"text-xl font-semibold text-white flex items-center gap-2"},E={class:"bg-gray-900 p-4 rounded-md space-y-3"},O={class:"text-gray-300 text-lg flex items-center"},T={class:"ml-2 font-semibold text-white"},z={class:"text-gray-300 text-lg flex items-center"},I={class:"ml-2 font-semibold text-white"},R={class:"text-gray-300 text-lg flex items-center"},U={class:"ml-2 font-semibold text-white"},G={class:"text-gray-300 text-lg flex items-center"},H={class:"ml-2 font-semibold text-white"},J={class:"text-gray-300 text-lg flex items-center"},K={class:"ml-2 font-semibold text-white"},P={__name:"ClientListItem",props:{client:{type:Object,required:!0}},emits:["close"],setup(n,{emit:r}){const d=b(!1),s=b(!1);function c(){d.value=!0}function f(){d.value=!1}function x(){s.value=!0}function g(){s.value=!1}return(m,t)=>(l(),a(v,null,[e("div",A,[e("div",L,[e("h2",q,[t[0]||(t[0]=e("span",{class:"text-indigo-300 text-2xl"},"👤",-1)),o(" "+u(n.client.nom),1)])]),e("div",E,[e("p",O,[t[1]||(t[1]=o(" 🏠 ")),e("span",T,u(n.client.adresse||"Non renseignée"),1)]),e("p",z,[t[2]||(t[2]=o(" 📮 ")),e("span",I,u(n.client.code_postal||"Non renseigné"),1)]),e("p",R,[t[3]||(t[3]=o(" 🏙 ")),e("span",U,u(n.client.ville||"Non renseignée"),1)]),e("p",G,[t[4]||(t[4]=o(" 🌍 ")),e("span",H,u(n.client.pays||"Non renseigné"),1)]),e("p",J,[t[5]||(t[5]=o(" 🔢 ")),e("span",K,u(n.client.siren||"Non renseigné"),1)])]),e("div",{class:"flex justify-center gap-3 mt-4"},[e("button",{onClick:c,class:"btn-action bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded flex items-center"}," ✏️ Modifier "),e("button",{onClick:x,class:"btn-action bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded flex items-center"}," 🗑️ Supprimer ")])]),d.value?(l(),p(i(h),{key:0,client:n.client,onClose:f},null,8,["client"])):y("",!0),s.value?(l(),p(i(Z),{key:1,client:n.client,onClose:g},null,8,["client"])):y("",!0)],64))}},Q={class:"fixed inset-0 bg-black/50 backdrop-blur-md flex items-center justify-center z-50"},W={class:"bg-white p-6 rounded-lg shadow-lg w-[24rem] animate-fade-in transform transition-transform scale-95"},X={class:"text-gray-700 mt-3"},Y={class:"text-gray-900"},Z={__name:"ClientDeleteModal",props:{client:{type:Object,required:!0}},emits:["close"],setup(n,{emit:r}){const d=n,s=r,c=_(),{deleteClient:f}=c;function x(){s("close")}async function g(){await f(d.client.id),x()}return(m,t)=>(l(),a("div",Q,[e("div",{onClick:k(x,["self"]),class:"absolute inset-0"}),e("div",W,[t[3]||(t[3]=e("div",{class:"flex items-center space-x-3"},[e("span",{class:"text-red-600 text-2xl"},"⚠️"),e("h2",{class:"text-lg font-semibold text-red-600"},"Confirmation de suppression")],-1)),e("p",X,[t[0]||(t[0]=o(" Êtes-vous sûr de vouloir supprimer le client ")),e("strong",Y,u(n.client.nom),1),t[1]||(t[1]=o(" ? "))]),t[4]||(t[4]=e("p",{class:"text-sm text-gray-500 mt-2"},[o(" Cette action est "),e("span",{class:"font-semibold text-red-500"},"irréversible"),o(". ")],-1)),e("div",{class:"flex justify-end mt-6 space-x-3"},[e("button",{onClick:x,class:"px-4 py-2 text-gray-700 border border-gray-300 rounded-md hover:bg-gray-100 transition"}," Annuler "),e("button",{onClick:g,class:"px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition flex items-center"},t[2]||(t[2]=[e("span",{class:"mr-2"},"🗑️",-1),o(" Supprimer ")]))])])]))}},ee={class:"bg-gray-900"},te={class:"mx-auto max-w-7xl"},se={class:"bg-gray-900 py-10"},ne={class:"px-4 sm:px-6 lg:px-8"},le={class:"sm:flex sm:items-center"},oe={class:"mt-4"},de={__name:"Client",setup(n){const r=b(!1);return(d,s)=>(l(),a("div",ee,[e("div",te,[e("div",se,[e("div",ne,[e("div",le,[s[2]||(s[2]=e("div",{class:"sm:flex-auto"},[e("h1",{class:"text-base font-semibold leading-6 text-white"},"Clients"),e("p",{class:"mt-2 text-sm text-gray-300"}," Une liste de touts les clients dans votre compte. ")],-1)),e("div",oe,[e("button",{onClick:s[0]||(s[0]=c=>r.value=!0),class:"bg-indigo-500 text-white px-4 py-2 rounded-md"},"Ajouter un client")])]),M(i(V)),r.value?(l(),p(i(h),{key:0,onClose:s[1]||(s[1]=c=>r.value=!1)})):y("",!0)])])])]))}};export{de as default};
