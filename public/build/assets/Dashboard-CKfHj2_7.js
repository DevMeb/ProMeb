import{s as T,e as P,c as n,a as o,b as e,d as S,f as v,h,u as t,F as j,i as $,t as a,n as A,o as R,r as D,j as z,g as w,k as M,v as B,l as N}from"./app-Jvx3VFZV.js";import{u as F,_ as H,a as U,b as q}from"./FactureFormModal-BGhsIUF8.js";import{u as I}from"./prestations-B3L3b5PY.js";/* empty css            */const L={class:"grid grid-cols-1 md:grid-cols-2 gap-6 mb-8"},E={__name:"PrestationsStatistiques",setup(l){const u=F(),{dashboardData:c}=T(u),g=P(!1),d=P(!1),b=P(!1),f=P(!1);return(m,i)=>(o(),n(j,null,[e("div",L,[h(t(k),{title:"Activité totale",value:t(c).prestations.length,description:"Total des prestations",icon:"📦",gradient:"from-indigo-500 to-indigo-600",textColor:"text-indigo-100",onClick:i[0]||(i[0]=x=>g.value=!0)},null,8,["value"]),h(t(k),{title:"Prestations en attente de facturation",value:t(c).prestations_unbilled.length,description:"Prestations en attente de facturation",icon:"⏳",gradient:"from-orange-400 to-orange-500",textColor:"text-orange-100",onClick:i[1]||(i[1]=x=>d.value=!0)},null,8,["value"]),h(t(k),{title:"Factures payées",value:t(c).factures_paid.length,description:"Factures régularisées",icon:"📑",gradient:"from-green-500 to-green-600",textColor:"text-green-100",onClick:i[2]||(i[2]=x=>b.value=!0)},null,8,["value"]),h(t(k),{title:"Factures non payées",value:t(c).factures_unpaid.length,description:"Factures en attente de facturation",icon:"⏳",gradient:"from-red-400 to-red-700",textColor:"text-orange-100",onClick:i[3]||(i[3]=x=>f.value=!0)},null,8,["value"])]),d.value?(o(),S(t(H),{key:0,onClose:i[4]||(i[4]=x=>d.value=!1)})):v("",!0),g.value?(o(),S(t(we),{key:1,onClose:i[5]||(i[5]=x=>g.value=!1)})):v("",!0),b.value?(o(),S(t(V),{key:2,factures:t(c).factures_paid,onClose:i[6]||(i[6]=x=>b.value=!1)},null,8,["factures"])):v("",!0),f.value?(o(),S(t(V),{key:3,factures:t(c).factures_unpaid,onClose:i[7]||(i[7]=x=>f.value=!1)},null,8,["factures"])):v("",!0)],64))}},W={class:"flex items-center mb-4 relative z-10"},G={class:"text-4xl mr-3 drop-shadow-md"},J={class:"text-xl font-semibold text-white drop-shadow-md"},K={class:"text-4xl font-bold text-white mb-2 drop-shadow-md relative z-10"},O={key:0,class:"pl-4"},Q={class:"text-sm text-white/80 relative z-10"},X={key:0,class:"absolute top-3 right-3 backdrop-blur-sm bg-red-400 text-white/90 text-xs font-medium px-3 py-1.5 rounded-full shadow-sm border border-red-500"},k={__name:"DashboardCard",props:{title:String,value:[String,Number],description:String,icon:String,gradient:String,badge:Number,taxe:Number,afterTaxe:Number,isDifference:Boolean,cursor:Boolean},setup(l){const u=l,c=$(()=>u.title.includes("CA")?`${Number(u.value).toLocaleString("fr-FR")} €`:u.value),g=$(()=>u.value>=0?"text-green-300":"text-red-300"),d=$(()=>u.value>=0?"▵":"▿");return $(()=>u.badge.includes("✔")?"border-green-600/30 text-green-300":u.badge.includes("⚠")?"border-orange-500/30 text-amber-300":"border-gray-500/30"),(b,f)=>(o(),n("div",{class:A(`bg-gradient-to-tr ${l.gradient} p-6 rounded-xl shadow-xl transform transition-all hover:scale-[1.02] hover:cursor-pointer hover:shadow-2xl relative overflow-hidden`)},[f[0]||(f[0]=e("div",{class:"absolute inset-0 bg-gradient-to-br from-white/10 to-transparent"},null,-1)),e("div",W,[e("span",G,a(l.icon),1),e("h2",J,a(l.title),1)]),e("p",K,[e("span",{class:A({"line-through text-gray-400":l.title==="CA facturé"&&l.taxe>0})},a(c.value),3),l.title==="CA facturé"&&l.taxe>0?(o(),n("span",O,a(l.afterTaxe)+" € ",1)):v("",!0),l.isDifference?(o(),n("span",{key:1,class:A(`text-lg ml-2 ${g.value}`)},a(d.value),3)):v("",!0)]),e("p",Q,a(l.description),1),l.title==="CA facturé"&&l.taxe>0?(o(),n("div",X," -"+a(l.taxe)+" % ",1)):v("",!0)],2))}},Y={class:"grid grid-cols-1 md:grid-cols-3 gap-6 mb-8"},Z={__name:"CAStatistiques",setup(l){const u=F(),{dashboardData:c,taxe:g,caBilledWithTax:d,caBilled:b,caAttendu:f,difference:m}=T(u);return(i,x)=>(o(),n("div",Y,[h(k,{title:"CA facturé",value:t(b),taxe:t(g),afterTaxe:t(d),badge:t(g),badgeColor:"text-red-500",description:"Revenus confirmés",icon:"💶",gradient:"from-green-600 to-green-700"},null,8,["value","taxe","afterTaxe","badge"]),h(k,{title:"CA attendu",value:t(f),description:"Revenus prévisionnels",icon:"📈",gradient:"from-amber-500 to-amber-600"},null,8,["value"]),h(k,{title:"Écart",value:t(m),description:"Différence CA réel/prévisionnel",icon:"⚖️",gradient:t(m)>=0?"from-green-600 to-green-700":"from-red-500 to-red-600",isDifference:!0},null,8,["value","gradient"])]))}},ee={class:"fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-md z-50"},te={class:"bg-gray-900 p-8 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-auto border border-gray-800"},se={class:"mt-6"},re={key:0,class:"p-6 text-center rounded-xl border-2 border-dashed border-gray-800 hover:border-indigo-500 transition-colors"},oe={key:1,class:"space-y-3"},ne={class:"ml-4 flex-1"},ae={class:"flex items-center justify-center gap-3"},le={class:"font-bold text-md bg-gray-900 text-gray-400 px-2 py-1 rounded-full"},ie={class:"grid grid-cols-2 gap-2 mt-2 font-bold"},de={class:"flex items-center justify-center gap-2 text-lg"},ue={class:"text-gray-300"},ce={class:"flex items-center justify-center gap-2 text-lg"},ge={class:"text-gray-300 truncate"},xe={class:"flex items-center justify-center gap-2 text-lg"},fe={class:"text-gray-300 truncate"},be={class:"flex items-center justify-center gap-2 text-lg"},me={class:"text-gray-300 truncate"},pe={key:0,class:"mt-6 bg-gray-800 p-4 rounded-xl border border-gray-700"},ve={class:"space-y-2"},ye={class:"flex justify-between items-center"},he={class:"text-indigo-400 font-bold"},_e={class:"flex justify-between items-center"},$e={class:"text-green-400 font-bold"},we={__name:"PrestationsModal",emits:["close"],setup(l,{emit:u}){const c=u,g=I(),{prestations:d}=T(g),{fetchPrestations:b}=g;R(()=>{b()});const f=$(()=>d.value.reduce((x,s)=>x+parseFloat(s.heures),0)),m=$(()=>d.value.reduce((x,s)=>x+s.taux_horaire.taux*s.heures,0)),i=()=>{c("close")};return(x,s)=>(o(),n("div",ee,[e("div",te,[e("div",{class:"flex justify-between items-center pb-4 border-b border-gray-800"},[s[0]||(s[0]=e("h2",{class:"text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400"}," 🧾 Prestations ",-1)),e("button",{onClick:i,class:"text-gray-400 hover:text-white transition-transform hover:scale-110"}," ✕ ")]),e("div",se,[t(d).length===0?(o(),n("div",re,s[1]||(s[1]=[e("div",{class:"text-4xl mb-3"},"🎉",-1),e("p",{class:"text-gray-400"},"Aucune prestations !",-1)]))):(o(),n("div",oe,[(o(!0),n(j,null,D(t(d),p=>(o(),n("label",{key:p.id,class:"group flex items-center p-4 rounded-xl border border-gray-800 hover:border-indigo-500 bg-gray-800/50 cursor-pointer transition-all"},[e("div",ne,[e("div",ae,[e("span",le,a(t(z)(p.date)),1)]),e("div",ie,[e("div",de,[s[2]||(s[2]=e("span",{class:"text-gray-500"},"🕒",-1)),e("span",ue,a(p.heures)+"h",1)]),e("div",ce,[s[3]||(s[3]=e("span",{class:"text-gray-500"},"📍",-1)),e("span",ge,a(p.adresse),1)]),e("div",xe,[s[4]||(s[4]=e("span",{class:"text-gray-500"},"👤",-1)),e("span",fe,a(p.client.nom),1)]),e("div",be,[s[5]||(s[5]=e("span",{class:"text-gray-500"},"💰",-1)),e("span",me,a(p.taux_horaire.taux)+" € / h",1)])])])]))),128))]))]),t(d).length!==0?(o(),n("div",pe,[s[8]||(s[8]=e("h3",{class:"text-lg font-semibold text-white mb-3 flex items-center gap-2"}," 📝 Récapitulatif ",-1)),e("div",ve,[e("div",ye,[s[6]||(s[6]=e("span",{class:"text-gray-400"},"Total heures :",-1)),e("span",he,a(f.value)+"h",1)]),e("div",_e,[s[7]||(s[7]=e("span",{class:"text-gray-400"},"Montant HT :",-1)),e("span",$e,a(m.value)+" €",1)])])])):v("",!0)])]))}},ke={class:"fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-md z-50"},Ce={class:"bg-gray-900 p-8 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-auto border border-gray-800"},Pe={class:"mt-6 space-y-4"},Se={key:0,class:"p-6 text-center rounded-xl border-2 border-dashed border-gray-800 hover:border-indigo-500 transition-colors"},Te={key:1},je={class:"flex items-center justify-between pb-2 border-b border-gray-700"},Ae={class:"font-bold text-md bg-gray-900 text-gray-400 px-3 py-1 rounded-full"},De=["onClick"],Fe=["onClick","disabled"],Me={key:0,class:"animate-spin border-4 border-white border-t-transparent rounded-full w-4 h-4"},Be={class:"grid grid-cols-2 gap-4 mt-3 text-lg"},Ne={class:"flex items-center gap-2 text-gray-300"},Ve={class:"font-semibold"},Re={class:"flex items-center gap-2 text-green-400"},ze={class:"font-semibold"},He={class:"mt-4 space-y-2"},Ue={class:"grid grid-cols-2 gap-4"},qe={class:"flex items-center gap-2 text-gray-400"},Ie={class:"font-semibold"},Le={class:"flex items-center gap-2 text-gray-400"},Ee={class:"font-semibold"},We={class:"flex items-center gap-2 text-gray-400"},Ge={class:"font-semibold"},Je={class:"flex items-center gap-2 text-green-400"},Ke={class:"font-semibold"},Oe={key:0,class:"mt-6 bg-gray-800 p-4 rounded-xl border border-gray-700"},Qe={class:"space-y-2"},Xe={class:"flex justify-between items-center"},Ye={class:"text-indigo-400 font-bold"},Ze={class:"flex justify-between items-center"},et={class:"text-green-400 font-bold"},V={__name:"FacturesModal",props:{factures:Array,required:!0},emits:["close"],setup(l,{emit:u}){const c=l,g=u,d=U(),{loading:b}=T(d),{paid:f}=d,m=P(null),i=$(()=>c.factures.reduce((C,r)=>C+parseFloat(r.heures_total),0)),x=$(()=>c.factures.reduce((C,r)=>C+r.montant_total,0));async function s(C){await f(C.id)}const p=()=>{g("close")};return(C,r)=>(o(),n("div",ke,[e("div",Ce,[e("div",{class:"flex justify-between items-center pb-4 border-b border-gray-800"},[r[1]||(r[1]=e("h2",{class:"text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400"}," 🧾 Factures Payées ",-1)),e("button",{onClick:p,class:"text-gray-400 hover:text-white transition-transform hover:scale-110"}," ✕ ")]),e("div",Pe,[l.factures.length===0?(o(),n("div",Se,r[2]||(r[2]=[e("div",{class:"text-4xl mb-3"},"🎉",-1),e("p",{class:"text-gray-400"},"Aucune facture disponible.",-1)]))):(o(),n("div",Te,[(o(!0),n(j,null,D(l.factures,y=>(o(),n("div",{key:y.id,class:"mt-2 bg-gray-800 p-4 rounded-lg shadow-md border border-gray-700"},[e("div",je,[e("span",Ae," 📄 Facture #"+a(y.id),1),e("button",{onClick:_=>m.value=y.id,class:"bg-indigo-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-indigo-500 transition"}," 📄 Voir PDF ",8,De),y.statut==="en_attente_paiement"?(o(),n("button",{key:0,onClick:_=>s(y),disabled:t(b).paid,class:"btn-action bg-green-500 disabled:opacity-50"},[t(b).paid?(o(),n("span",Me)):v("",!0),r[3]||(r[3]=w(" ✅ Payé "))],8,Fe)):v("",!0)]),e("div",Be,[e("div",Ne,[r[4]||(r[4]=w(" 🕒 ")),e("span",Ve,a(y.heures_total)+" h",1)]),e("div",Re,[r[5]||(r[5]=w(" 💰 ")),e("span",ze,a(y.montant_total)+" €",1)])]),e("div",He,[r[10]||(r[10]=e("h3",{class:"text-md font-semibold text-gray-300 flex items-center gap-2"}," 📌 Prestations incluses : ",-1)),(o(!0),n(j,null,D(y.prestations,_=>(o(),n("div",{key:_.id,class:"bg-gray-800 p-3 rounded-lg border border-gray-700"},[e("div",Ue,[e("div",qe,[r[6]||(r[6]=w(" 📅 ")),e("span",Ie,a(t(z)(_.date)),1)]),e("div",Le,[r[7]||(r[7]=w(" 🏢 ")),e("span",Ee,a(_.client.nom),1)]),e("div",We,[r[8]||(r[8]=w(" ⏰ ")),e("span",Ge,a(_.heures)+" h",1)]),e("div",Je,[r[9]||(r[9]=w(" 💵 ")),e("span",Ke,a(_.taux_horaire.taux)+" € / h",1)])])]))),128))]),m.value===y.id?(o(),S(q,{key:0,invoice:y,onClose:r[0]||(r[0]=_=>m.value=null)},null,8,["invoice"])):v("",!0)]))),128))]))]),l.factures.length!==0?(o(),n("div",Oe,[r[13]||(r[13]=e("h3",{class:"text-lg font-semibold text-white mb-3 flex items-center gap-2"}," 📝 Récapitulatif ",-1)),e("div",Qe,[e("div",Xe,[r[11]||(r[11]=e("span",{class:"text-gray-400"},"Total heures :",-1)),e("span",Ye,a(i.value)+" h",1)]),e("div",Ze,[r[12]||(r[12]=e("span",{class:"text-gray-400"},"Montant HT :",-1)),e("span",et,a(x.value)+" €",1)])])])):v("",!0)])]))}},tt={class:"p-6 bg-gray-900 min-h-screen"},st={class:"mb-8 flex flex-wrap gap-4 items-center"},rt={key:0,class:"text-center text-gray-300"},ot={key:1,class:"text-center text-red-400"},nt={key:2},at={key:3,class:"text-center text-gray-400"},ct={__name:"Dashboard",setup(l){const u=F(),{dashboardData:c,period:g,taxe:d,loading:b,error:f}=T(u),{fetchDashboard:m}=u;function i(){m()}return R(()=>{m()}),(x,s)=>(o(),n("div",tt,[s[4]||(s[4]=e("h1",{class:"text-3xl font-bold text-white mb-8"},"Tableau de bord",-1)),e("div",st,[e("div",null,[s[2]||(s[2]=e("label",{for:"period",class:"text-white font-semibold mr-2"},"Période :",-1)),M(e("input",{type:"month",id:"period","onUpdate:modelValue":s[0]||(s[0]=p=>N(g)?g.value=p:null),onChange:i,class:"p-2 rounded border border-gray-600 bg-gray-800 text-white"},null,544),[[B,t(g)]])]),e("div",null,[s[3]||(s[3]=e("label",{for:"taxe",class:"text-white font-semibold mr-2"},"Taxe (%) :",-1)),M(e("input",{type:"number",id:"taxe","onUpdate:modelValue":s[1]||(s[1]=p=>N(d)?d.value=p:null),min:"0",class:"p-2 rounded border border-gray-600 bg-gray-800 text-white w-24 text-center"},null,512),[[B,t(d)]])])]),t(b)?(o(),n("div",rt,"Chargement...")):t(f)?(o(),n("div",ot,a(t(f)),1)):t(c)&&t(c).prestations&&t(c).prestations.length>0?(o(),n("div",nt,[h(t(E)),h(t(Z))])):(o(),n("div",at," 📉 Aucune donnée disponible pour ce mois. "))]))}};export{ct as default};
