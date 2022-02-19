// Your web app's Firebase configuration
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
var firebaseConfig = {
	apiKey: "AIzaSyCEJIILM7Y52o5z-bYCL0o7Adbi3S1Ckcs",
	authDomain: "ecoma-312816.firebaseapp.com",
	projectId: "ecoma-312816",
	storageBucket: "ecoma-312816.appspot.com",
	messagingSenderId: "333681157443",
	appId: "1:333681157443:web:6d25f0978f72a577d6bbf6",
	measurementId: "G-KKDLSV35G2"
};
// Initialize Firebase
firebase.initializeApp(firebaseConfig);
firebase.analytics();

const auth = firebase.auth();
const provider = new firebase.auth.GoogleAuthProvider();
const loginBtn = document.getElementById('signInBtn');

loginBtn.onclick = () => auth.signInWithPopup(provider);

auth.onAuthStateChange(user => {
	if(user){
		//when signed In
	}else{
		//when signed Out
	}
})