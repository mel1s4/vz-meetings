import React from 'react';
import ReactDOM from 'react-dom/client';
import './index.scss';
import App from './App';
import reportWebVitals from './reportWebVitals';

const root = ReactDOM.createRoot(document.getElementById('vz-calendar'));

// function rendertVzCalendarWindows() {
//   const calendars = document.querySelectorAll('.vz-calendar');
//   calendars.forEach((calendar) => {
//     const calendar_id = calendar.getAttribute('data-vz-calendar-id');
//     id = 'vz_calendar_' + calendar_id;
//     root.render(
//       <React.StrictMode>
//         <App id={id} />
//       </React.StrictMode>
//     );
//   });

//   }

root.render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);

// If you want to start measuring performance in your app, pass a function
// to log results (for example: reportWebVitals(console.log))
// or send to an analytics endpoint. Learn more: https://bit.ly/CRA-vitals
reportWebVitals();
