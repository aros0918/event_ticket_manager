<template>
  <div>
    <b-calendar
      today-variant="info"
      nav-button-variant="primary"
      v-model="value"
      :date-disabled-fn="dateDisabled"
      block
      locale="en-US"
    ></b-calendar>
    <div class="scroll-container" style="overflow: auto;">
    
      <b-button-group style="margin-top: 20px; margin-bottom: 5px;">
        <b-button
          style="margin-left: 10px; border-radius: 8px;"
          v-for="(button, index) in buttons"
          :key="index"
          @click="handleButtonClick(button)"
          :variant="button.clicked ? 'success' : 'primary'"
        >
          {{ button.label }}
        </b-button>
      </b-button-group>
      
    </div>
    <!-- Numeric Input -->
    <div class="outer-container">
      <div>
        <p>&nbsp;<b-icon icon="cart-plus-fill" scale="1.5" aria-hidden="true"></b-icon><b> &nbsp;General Ticket</b></p>
      </div>
      <div style="display: flex; align-items: center; justify-content: end;">
        <div>
          Add for <b>MX$380.00</b>
        </div>
        <div class="numeric-input">
          <button @click="decrement">-</button>
          <div style="width: 20px; text-align: center;" v-model="numericValue" > {{numericValue}}</div>
          <button style="background-color: #0d6efd;  color: #fff; " @click="increment">+</button> 
        </div>
      </div>
    </div>

    <div class="outer-container">
      <div>
        <p>&nbsp;<b-icon icon="cart-plus-fill" scale="1.5" aria-hidden="true"></b-icon><b> &nbsp;VIP Ticket</b></p>
      </div>
      <div style="display: flex; align-items: center; justify-content: end;">
        <div>
          Add for <b>MX$580.00</b>
        </div>
        <div class="numeric-input">
          <button @click="decrement1">-</button>
          <div style="width: 20px; text-align: center;" v-model="numericValue1" > {{numericValue1}}</div>
          <button style="background-color: #0d6efd;  color: #fff; " @click="increment1">+</button> 
        </div>
      </div>
    </div>
    <div class="d-grid" style="margin-top:20px">
      <a class="btn btn-primary btn-lg " @click="handleUser">
          <i class="fas fa-ticket-alt"></i>
          MX$ {{totalPrice}} - Get it
      </a>
    </div>
  </div>
</template>

<script>
import { BRow, BCol, BCalendar, BButton, BButtonGroup, BIcon } from 'bootstrap-vue';
import { loadStripe } from '@stripe/stripe-js';
import { mapState } from "vuex";
import mixinsFilters from "../../mixins.js";
import _ from "lodash";
import moment from 'moment-timezone';

export default {
  name: 'TicketCalendar',
  props: {
    startTime: {
      type: String,
      required: true,
    },
    endTime: {
      type: String,
      required: true,
    },
    intervalTime: {
      type: Number,
      required: true,
    },
    disableDate: {
      type: String,
      required: true,
    },
    loginUser: {
      type: Number,
      required : false,
    },
    eventId: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      value: '', // Initialize value with the prop value
      buttons: [], // Initialize buttons array
      numericValue: 0,
      numericValue1: 0,
      price: 0,
      quantity: 0,
    };
  },
  watch: {
    value(newValue) {
      console.log(newValue); // Log the value whenever it changes
    },
    startTime() {
      this.generateButtons(); // Regenerate buttons when startTime changes
    },
    endTime() {
      this.generateButtons(); // Regenerate buttons when startTime changes
    },
    intervalTime() {
      this.generateButtons(); // Regenerate buttons when intervalTime changes
    },
  },
  mounted() {
    this.generateButtons(); // Generate buttons on component mount
  },
  computed: {
    totalPrice() {
      // Calculate total price based on numericValue and numericValue1
      const priceMixology = 380 * this.numericValue;
      const priceVIP = 580 * this.numericValue1;
      this.quantity = this.numericValue + this.numericValue1;
      this.price = priceMixology + priceVIP;
      return priceMixology + priceVIP;
    },
  },
  methods: {
    async fetchPaymentUrl() {
      try {

        let currentTime = moment().format('YYYY-MM-DD');
        let baseUrl = window.location.href.split('?')[0];

        // Construct the new URL with the desired parameters
        let newUrl = `${baseUrl}?price=${this.price}&quantity_general=${this.numericValue}&quantity_vip=${this.numericValue1}&event_id=${this.eventId}&booking_time=${currentTime}&customer_id=${this.loginUser}`;

        const response = await axios.post(route('create_payment_intent'),
        {
            price: this.price,
            success_url: newUrl,
        })
        window.location.href = response.data.url;
      } catch (error) {
          console.log("error", error);
      }
    },
    handleUser() {
        if (this.loginUser != -1) {
          this.fetchPaymentUrl(); 
        } else {
          window.location.href = '/login';
        }
    },
    dateDisabled(ymd, date) {
      const weekday = date.getDay();
      const day = date.getDate();
      return weekday === this.disableDate;
    },
    generateButtons() {
      const buttons = [];
      let currentTime = this.startTime;
      let i = 0;
      while (currentTime <= this.endTime) {
        i++;
        buttons.push({
          id : i,
          label: currentTime,
          clicked: false,
        });
        currentTime = this.addMinutes(currentTime, this.intervalTime);
      }

      this.buttons = buttons;
    },
    handleButtonClick(clickedButton) {
      this.buttons.forEach(button => {
        if (button.id === clickedButton.id) {
          button.clicked = true;
          console.log(`Button ${button.label} clicked.`);
        } else {
          button.clicked = false;
        }
      });
    },
    increment() {
      if (this.numericValue + this.numericValue1 < 10) {
        this.numericValue++;
      }
    },
    decrement() {
      if (this.numericValue > 0) {
        this.numericValue--;
      }
    },
    increment1() {
      if (this.numericValue + this.numericValue1 < 10) {
        this.numericValue1++;
      } 
    },
    decrement1() {
      if (this.numericValue1 > 0) {
        this.numericValue1--;
      }
    },
    addMinutes(timeString, minutes) {
      const [hours, minutesPart] = timeString.split(':').map(Number);
      const totalMinutes = hours * 60 + minutesPart + minutes;
      const newHours = Math.floor(totalMinutes / 60);
      const newMinutes = totalMinutes % 60;
      return `${String(newHours).padStart(2, '0')}:${String(newMinutes).padStart(2, '0')}`;
    },

  },
  components: {
    BRow,
    BCol,
    BCalendar,
    BButton,
    BButtonGroup,
    BIcon,
  },
};
</script>

<style>
.scroll-container {
  max-height: 200px; /* Set the maximum height of the scrollable area */
  overflow-y: auto; /* Enable vertical scrolling */
}

/* Custom scrollbar styles */
::-webkit-scrollbar {
  width: 8px; /* Set the width of the scrollbar */
  height: 8px; /* Set the height of the scrollbar */
}

::-webkit-scrollbar-thumb {
  background-color: #888; /* Set the color of the scrollbar thumb */
  border-radius: 4px; /* Make the scrollbar thumb round */
}

::-webkit-scrollbar-track {
  background-color: #f1f1f1; /* Set the color of the scrollbar track */
  border-radius: 4px; /* Make the scrollbar track round */
}

.numeric-input {
  margin-left: 10px;
  background-color: #fff;
  margin-top: 0px;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 45px;
  width: 120px;
  border-radius: 20px;
}

.numeric-input input{
  align-items: center;
  justify-content: center;
}
.numeric-input button {
  font-size: 18px;
  font-weight: bold;
  margin: 0 5px;
  cursor: pointer;
  border: none;
  box-shadow: none;
  width: 35px;
  height: 35px;
  border-radius: 50%; /* Adjust the border radius as needed */
}
.numeric-input button:hover {
  background-color: #f0f0f0; /* Change background color on hover */
}

.numeric-input button:focus {
  outline: none; /* Remove outline when button is focused */
}

.numeric-input button:active {
  box-shadow: 0 0 5px rgba(0, 0, 0, 0.3); /* Add box shadow when button is clicked */
}
.outer-container {
  background-color: #f0ebfd;
  margin-top: 20px;
  width: 100%;
  border-radius: 8px; /* Adjust the border radius as needed */
  border: 1px solid #ccc; /* Add border for visibility */
  padding: 10px; /* Add padding for spacing */
}
</style>