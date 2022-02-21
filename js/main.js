const app = new Vue({
	el: '#app',
	data() {
		return {
			name: '',
			email: '',
			phone: '',
			price: '',
			response: '',
			activeClass: 'active',
			regEmail: /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,24}))$/
		}
	},
	methods: {
		submitForm() {
			axios.post('https://test.vadev.ru/testHR/amo.php', {
				name: this.name,
				email: this.email,
				phone: this.phone,
				price: this.price
			}).then(response => {
				this.response = response.data
			})
		},
		isEmailValid: function() {
			return (this.email == "")? "" : (this.regEmail.test(this.email)) ? 'has-success' : 'has-error';
		},
		isPriceValid: function() {
			return (this.price == "")? "" : (this.price.length > 2) ? 'has-success' : 'has-error';
		},
		isNameValid: function() {
			return (this.name == "")? "" : (this.name.length > 1) ? 'has-success' : 'has-error';
		}
	}
})