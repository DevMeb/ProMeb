import{s as $,o as Z,c as r,a as s,h as N,u as e,b as t,t as i,F as E,r as T,d as C,e as j,C as ct,f as _,w as B,g as v,k as w,D,n as M,v as F,j as tt,E as xt}from"./app-Jvx3VFZV.js";import{u as V}from"./prestations-B3L3b5PY.js";import{u as et,_ as st}from"./ClientFormModal-CfEq8JED.js";import{u as ot,_ as mt}from"./TauxHoraireFormModal-C6OlOoSD.js";/* empty css            */const pt={class:"mt-6"},ft={key:0,class:"flex justify-center my-6"},vt={key:1,class:"flex justify-center my-6 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg"},gt={class:"text-lg font-medium ml-2"},bt={key:2,class:"flex justify-center my-6 bg-gray-800 px-6 py-4 rounded-lg shadow-lg"},yt={key:3,class:"grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-8"},_t={__name:"PrestationList",setup(x){const g=V(),{fetchPrestations:b,clearErrors:a}=g,{filteredPrestations:d,errors:f,loading:h}=$(g);return Z(()=>{b(),a("fetch")}),(k,o)=>(s(),r("div",pt,[N(e(ue)),N(e(_e)),e(h).fetch?(s(),r("div",ft,o[0]||(o[0]=[t("div",{class:"animate-spin inline-block w-6 h-6 border-4 border-white border-t-transparent rounded-full"},null,-1),t("p",{class:"text-white text-lg font-medium ml-2"},"Chargement des prestations...",-1)]))):e(f).fetch?(s(),r("div",vt,[o[1]||(o[1]=t("span",{class:"text-xl"},"❌",-1)),t("p",gt,i(e(f).fetch),1)])):e(d).length===0?(s(),r("div",bt,o[2]||(o[2]=[t("p",{class:"text-gray-300 text-lg"},"📭 Aucune prestation trouvée.",-1)]))):(s(),r("div",yt,[(s(!0),r(E,null,T(e(d),l=>(s(),C(e(ie),{prestation:l,key:l.id},null,8,["prestation"]))),128))]))]))}},ht={class:"fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-md z-50"},wt={class:"bg-white p-6 rounded-xl shadow-xl w-[90%] sm:w-96 transform transition-all animate-fade-in"},kt={class:"flex items-center justify-between border-b pb-2"},$t={class:"text-xl font-semibold text-gray-800 flex items-center"},Ct={key:0,class:"mr-2"},jt={key:1,class:"mr-2"},Mt={class:"relative"},Et=["disabled"],St=["value"],Pt={key:0,class:"text-red-500 text-xs mt-1"},Ft={class:"relative"},Tt=["disabled"],Vt=["value"],At={key:0,class:"text-red-500 text-xs mt-1"},Dt={key:0,class:"text-red-500 text-xs mt-1"},Ht={key:0,class:"text-red-500 text-xs mt-1"},Ut={key:0,class:"text-red-500 text-xs mt-1"},Nt={key:0,class:"text-red-500 text-xs mt-1"},Bt={class:"flex justify-end space-x-3 mt-4"},zt=["disabled"],Lt={key:0,class:"animate-spin mr-2"},rt={__name:"PrestationFormModal",props:{prestation:Object},emits:["close"],setup(x,{emit:g}){const b=x,a=g,d=V(),{addPrestation:f,updatePrestation:h,clearErrors:k}=d,{errors:o,loading:l}=$(d),S=et(),{fetchClients:m}=S,{clients:P}=$(S),u=ot(),{fetchTauxHoraires:y}=u,{tauxHoraires:z}=$(u),c=j({id:null,date:"",heures:"",adresse:"",horaires:"",client_id:"",taux_horaire_id:""}),H=j(!1),nt=()=>{H.value=!0},it=()=>{H.value=!1,y()},U=j(!1),lt=()=>{U.value=!0},at=()=>{U.value=!1,m()};ct(()=>{c.value=b.prestation?{...b.prestation}:{id:null,date:"",heures:"",adresse:"",horaires:"",client_id:"",taux_horaire_id:""},m(),y()});const dt=async()=>{(c.value.id?await h(c.value):await f(c.value))&&A()},A=()=>{k("validationErrors"),a("close")};return(ut,n)=>{var L,O,q,R,I,G,J,K,Q,W,X,Y;return s(),r("div",ht,[t("div",{onClick:B(A,["self"]),class:"fixed inset-0"}),t("div",wt,[t("div",kt,[t("h2",$t,[c.value.id?(s(),r("span",Ct,"✏️")):(s(),r("span",jt,"➕")),v(" "+i(c.value.id?"Éditer la prestation":"Ajouter une prestation"),1)]),t("button",{onClick:A,class:"text-gray-500 hover:text-gray-700 transition"},"✖️")]),t("form",{onSubmit:B(dt,["prevent"]),class:"mt-4 space-y-4"},[t("div",null,[n[6]||(n[6]=t("label",{for:"client",class:"block text-sm font-medium text-gray-700"},"Client",-1)),t("div",Mt,[w(t("select",{id:"client","onUpdate:modelValue":n[0]||(n[0]=p=>c.value.client_id=p),class:M(["w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500",{"border-red-500":(L=e(o).validationErrors)==null?void 0:L.client_id}])},[t("option",{disabled:e(P).length===0,value:""},"Sélectionner un client",8,Et),(s(!0),r(E,null,T(e(P),p=>(s(),r("option",{key:p.id,value:p.id},i(p.nom),9,St))),128))],2),[[D,c.value.client_id]]),(O=e(o).validationErrors)!=null&&O.client_id?(s(),r("p",Pt,i(e(o).validationErrors.client_id.join(", ")),1)):_("",!0)]),t("button",{type:"button",onClick:lt,class:"mt-2 text-indigo-600 text-sm flex items-center hover:underline"}," ➕ Ajouter un client ")]),t("div",null,[n[7]||(n[7]=t("label",{for:"taux-horaire",class:"block text-sm font-medium text-gray-700"},"Taux Horaire",-1)),t("div",Ft,[w(t("select",{id:"taux-horaire","onUpdate:modelValue":n[1]||(n[1]=p=>c.value.taux_horaire_id=p),class:M(["w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500",{"border-red-500":(q=e(o).validationErrors)==null?void 0:q.taux_horaire_id}])},[t("option",{disabled:e(z).length===0,value:""},"Sélectionner un taux horaire",8,Tt),(s(!0),r(E,null,T(e(z),p=>(s(),r("option",{key:p.id,value:p.id},i(p.taux),9,Vt))),128))],2),[[D,c.value.taux_horaire_id]]),(R=e(o).validationErrors)!=null&&R.taux_horaire_id?(s(),r("p",At,i(e(o).validationErrors.taux_horaire_id.join(", ")),1)):_("",!0)]),t("button",{type:"button",onClick:nt,class:"mt-2 text-indigo-600 text-sm flex items-center hover:underline"}," ➕ Ajouter un taux horaire ")]),t("div",null,[n[8]||(n[8]=t("label",{for:"date",class:"block text-sm font-medium text-gray-700"},"Date de prestation",-1)),w(t("input",{type:"date",id:"date","onUpdate:modelValue":n[2]||(n[2]=p=>c.value.date=p),class:M(["w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500",{"border-red-500":(I=e(o).validationErrors)==null?void 0:I.date}])},null,2),[[F,c.value.date]]),(G=e(o).validationErrors)!=null&&G.date?(s(),r("p",Dt,i(e(o).validationErrors.date.join(", ")),1)):_("",!0)]),t("div",null,[n[9]||(n[9]=t("label",{for:"heures",class:"block text-sm font-medium text-gray-700"},"Nombre d'heures",-1)),w(t("input",{type:"number",id:"heures","onUpdate:modelValue":n[3]||(n[3]=p=>c.value.heures=p),class:M(["w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500",{"border-red-500":(J=e(o).validationErrors)==null?void 0:J.heures}])},null,2),[[F,c.value.heures]]),(K=e(o).validationErrors)!=null&&K.heures?(s(),r("p",Ht,i(e(o).validationErrors.heures.join(", ")),1)):_("",!0)]),t("div",null,[n[10]||(n[10]=t("label",{for:"adresse",class:"block text-sm font-medium text-gray-700"},"Adresse",-1)),w(t("input",{type:"text",id:"adresse","onUpdate:modelValue":n[4]||(n[4]=p=>c.value.adresse=p),class:M(["w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500",{"border-red-500":(Q=e(o).validationErrors)==null?void 0:Q.adresse}])},null,2),[[F,c.value.adresse]]),(W=e(o).validationErrors)!=null&&W.adresse?(s(),r("p",Ut,i(e(o).validationErrors.adresse.join(", ")),1)):_("",!0)]),t("div",null,[n[11]||(n[11]=t("label",{for:"horaires",class:"block text-sm font-medium text-gray-700"},"Horaires",-1)),w(t("input",{type:"text",id:"horaires","onUpdate:modelValue":n[5]||(n[5]=p=>c.value.horaires=p),class:M(["w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500",{"border-red-500":(X=e(o).validationErrors)==null?void 0:X.horaires}])},null,2),[[F,c.value.horaires]]),(Y=e(o).validationErrors)!=null&&Y.horaires?(s(),r("p",Nt,i(e(o).validationErrors.horaires.join(", ")),1)):_("",!0)]),t("div",Bt,[t("button",{type:"button",onClick:A,class:"px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 transition"}," Annuler "),t("button",{type:"submit",class:"px-4 py-2 bg-indigo-600 text-white rounded flex items-center hover:bg-indigo-700 transition",disabled:c.value.id?e(l).update:e(l).add},[(c.value.id?e(l).update:e(l).add)?(s(),r("span",Lt,"⏳")):_("",!0),v(" "+i(c.value.id?"Mettre à jour":"Ajouter"),1)],8,zt)])],32)]),U.value?(s(),C(e(st),{key:0,onClose:at})):_("",!0),H.value?(s(),C(e(mt),{key:1,onClose:it})):_("",!0)])}}},Ot={class:"bg-gray-800 p-6 rounded-lg shadow-lg flex flex-col space-y-4 hover:shadow-xl transition-all transform hover:scale-[1.02] border border-gray-700"},qt={class:"flex justify-between items-center border-b pb-3"},Rt={class:"text-xl font-semibold text-white flex items-center gap-2"},It={class:"bg-gray-900 p-4 rounded-md grid grid-cols-1 sm:grid-cols-2 gap-4"},Gt={class:"space-y-3"},Jt={class:"text-gray-300 text-lg flex items-center"},Kt={class:"ml-2 font-semibold text-white"},Qt={class:"text-gray-300 text-lg flex items-center"},Wt={class:"ml-2 font-semibold text-white"},Xt={class:"space-y-3"},Yt={class:"text-gray-300 text-lg flex items-center"},Zt={class:"ml-2 font-semibold text-white"},te={class:"text-gray-300 text-lg flex items-center"},ee={class:"ml-2 font-semibold text-white"},se={class:"bg-gray-900 p-4 rounded-lg flex flex-col space-y-3"},oe={class:"text-lg font-semibold text-white flex items-center gap-2"},re={class:"bg-gray-900 p-4 rounded-lg flex flex-col space-y-3"},ne={class:"text-lg font-semibold text-white flex items-center gap-2"},ie={__name:"PrestationListItem",props:{prestation:{type:Object,required:!0}},emits:["close"],setup(x,{emit:g}){const b=j(!1),a=j(!1),d=j(!1);function f(){b.value=!0}function h(){b.value=!1}function k(){a.value=!0}function o(){a.value=!1}function l(){d.value=!1}return(S,m)=>(s(),r(E,null,[t("div",Ot,[t("div",qt,[t("h2",Rt,[m[0]||(m[0]=t("span",{class:"text-indigo-300 text-2xl"},"💼",-1)),v(" Prestation N° "+i(x.prestation.id),1)])]),t("div",It,[t("div",Gt,[t("p",Jt,[m[1]||(m[1]=v(" 🗓 ")),t("span",Kt,i(e(tt)(x.prestation.date)),1)]),t("p",Qt,[m[2]||(m[2]=v(" ⏰ ")),t("span",Wt,i(x.prestation.heures)+" h",1)])]),t("div",Xt,[t("p",Yt,[m[3]||(m[3]=v(" 📍 ")),t("span",Zt,i(x.prestation.adresse),1)]),t("p",te,[m[4]||(m[4]=v(" 🕒 ")),t("span",ee,i(x.prestation.horaires),1)])])]),t("div",se,[t("h3",oe,[m[5]||(m[5]=t("span",{class:"text-indigo-300 text-xl"},"👤",-1)),v(" "+i(x.prestation.client.nom),1)])]),t("div",re,[t("h3",ne,[m[6]||(m[6]=t("span",{class:"text-indigo-300 text-xl"},"💰",-1)),v(" "+i(x.prestation.taux_horaire.taux)+" € / h ",1)])]),t("div",{class:"flex justify-center gap-3 mt-4"},[t("button",{onClick:f,class:"btn-action bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded flex items-center"}," ✏️ Modifier "),t("button",{onClick:k,class:"btn-action bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded flex items-center"}," 🗑️ Supprimer ")])]),b.value?(s(),C(e(rt),{key:0,prestation:x.prestation,onClose:h},null,8,["prestation"])):_("",!0),a.value?(s(),C(e(fe),{key:1,prestation:x.prestation,onClose:o},null,8,["prestation"])):_("",!0),d.value?(s(),C(e(st),{key:2,client:x.prestation.client,onClose:l},null,8,["client"])):_("",!0)],64))}},le={class:"bg-gray-800 p-4 rounded-lg shadow-lg flex flex-col sm:flex-row gap-4"},ae=["value"],de=["value"],ue={__name:"PrestationsFilters",setup(x){const g=V(),b=et(),a=ot(),{activeFilters:d,isAnyFilterActive:f}=$(g),{updateFilters:h}=g,{fetchClients:k}=b,{clients:o}=$(b),{fetchTauxHoraires:l}=a,{tauxHoraires:S}=$(a);Z(()=>{k(),l()}),xt(d,P=>{h(P)},{deep:!0});const m=()=>{h({month_year:"",client_id:"",taux_horaire_id:""})};return(P,u)=>(s(),r("div",le,[u[8]||(u[8]=t("div",{class:"flex-1"},[t("p",{class:"text-gray-300 text-sm mb-2"}," Filtrer les prestations. ")],-1)),t("div",null,[u[3]||(u[3]=t("label",{class:"text-sm text-gray-300 font-semibold"},"Mois & Année :",-1)),w(t("input",{type:"month","onUpdate:modelValue":u[0]||(u[0]=y=>e(d).month_year=y),class:"filter-input"},null,512),[[F,e(d).month_year]])]),t("div",null,[u[5]||(u[5]=t("label",{class:"text-sm text-gray-300 font-semibold"},"Client :",-1)),w(t("select",{"onUpdate:modelValue":u[1]||(u[1]=y=>e(d).client_id=y),class:"filter-input"},[u[4]||(u[4]=t("option",{value:""},"Tous les clients",-1)),(s(!0),r(E,null,T(e(o),y=>(s(),r("option",{key:y.id,value:y.id},i(y.nom),9,ae))),128))],512),[[D,e(d).client_id]])]),t("div",null,[u[7]||(u[7]=t("label",{class:"text-sm text-gray-300 font-semibold"},"Taux Horaire :",-1)),w(t("select",{"onUpdate:modelValue":u[2]||(u[2]=y=>e(d).taux_horaire_id=y),class:"filter-input"},[u[6]||(u[6]=t("option",{value:""},"Tous les taux horaires",-1)),(s(!0),r(E,null,T(e(S),y=>(s(),r("option",{key:y.id,value:y.id},i(y.taux),9,de))),128))],512),[[D,e(d).taux_horaire_id]])]),e(f)?(s(),r("button",{key:0,onClick:m,class:"btn-secondary"}," Réinitialiser ")):_("",!0)]))}},ce={class:"fixed inset-0 bg-black/50 backdrop-blur-md flex items-center justify-center z-50"},xe={class:"bg-white p-6 rounded-lg shadow-lg w-[24rem] animate-fade-in transform transition-transform scale-95"},me={class:"text-gray-700 mt-3"},pe={class:"text-gray-900"},fe={__name:"PrestationDeleteModal",props:{prestation:{type:Object,required:!0}},emits:["close"],setup(x,{emit:g}){const b=x,a=g,d=V(),{deletePrestation:f}=d;function h(){a("close")}async function k(){await f(b.prestation.id),h()}return(o,l)=>(s(),r("div",ce,[t("div",{onClick:B(h,["self"]),class:"absolute inset-0"}),t("div",xe,[l[3]||(l[3]=t("div",{class:"flex items-center space-x-3"},[t("span",{class:"text-red-600 text-2xl"},"⚠️"),t("h2",{class:"text-lg font-semibold text-red-600"},"Confirmation de suppression")],-1)),t("p",me,[l[0]||(l[0]=v(" Êtes-vous sûr de vouloir supprimer la prestation du ")),t("strong",pe,i(e(tt)(x.prestation.date_prestation)),1),l[1]||(l[1]=v(" ? "))]),l[4]||(l[4]=t("p",{class:"text-sm text-gray-500 mt-2"},[v(" Cette action est "),t("span",{class:"font-semibold text-red-500"},"irréversible"),v(". ")],-1)),t("div",{class:"flex justify-end mt-6 space-x-3"},[t("button",{onClick:h,class:"px-4 py-2 text-gray-700 border border-gray-300 rounded-md hover:bg-gray-100 transition"}," Annuler "),t("button",{onClick:k,class:"px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition flex items-center"},l[2]||(l[2]=[t("span",{class:"mr-2"},"🗑️",-1),v(" Supprimer ")]))])])]))}},ve={class:"bg-gray-800 text-gray-300 p-4 rounded-lg shadow flex flex-col sm:flex-row justify-between items-center text-white mt-4"},ge={class:"mb-2 sm:mb-0"},be={class:"font-bold text-lg"},ye={class:"font-bold text-lg"},_e={__name:"PrestationSummary",setup(x){const g=V(),{prestationCount:b,totalHours:a}=$(g);return(d,f)=>(s(),r("div",ve,[t("div",ge,[f[0]||(f[0]=v(" Nombre de prestations : ")),t("span",be,i(e(b)),1)]),t("div",null,[f[1]||(f[1]=v(" Total d'heures : ")),t("span",ye,i(e(a)),1),f[2]||(f[2]=v(" h "))])]))}},he={class:"bg-gray-900"},we={class:"mx-auto max-w-7xl"},ke={class:"bg-gray-900 py-10"},$e={class:"px-4 sm:px-6 lg:px-8"},Ce={class:"sm:flex sm:items-center"},je={class:"mt-4"},Te={__name:"Prestation",setup(x){const g=j(!1);return(b,a)=>(s(),r("div",he,[t("div",we,[t("div",ke,[t("div",$e,[t("div",Ce,[a[2]||(a[2]=t("div",{class:"sm:flex-auto"},[t("h1",{class:"text-base font-semibold leading-6 text-white"},"Prestations"),t("p",{class:"mt-2 text-sm text-gray-300"}," Une liste de toutes les prestations dans votre compte. ")],-1)),t("div",je,[t("button",{onClick:a[0]||(a[0]=d=>g.value=!0),class:"bg-indigo-500 text-white px-4 py-2 rounded-md"},"Ajouter une prestation")])]),N(e(_t)),g.value?(s(),C(e(rt),{key:0,onClose:a[1]||(a[1]=d=>g.value=!1)})):_("",!0)])])])]))}};export{Te as default};
