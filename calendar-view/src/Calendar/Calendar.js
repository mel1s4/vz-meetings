import React, { useState, useEffect } from 'react';
import { _vz, Month, formatDate } from '../translations';
import './Calendar.scss';

export default function Calendar({
  selectedMonth,
  selectedYear,
  selectedDay,
  setSelectedDay,
  monthIsLoading,
  monthAvailability,
  highlightedDatetime,
  setSelectedMonth,
  setSelectedYear,
}) {

  function dayIsHighlighted(day) {
    const month = selectedMonth + 1;
    const year = selectedYear;
    if (highlightedDatetime) {
      const [hYear, hMonth, hDay] = formatDate(highlightedDatetime).split('-');
      return parseInt(hDay) === day && parseInt(hMonth) === month && parseInt(hYear) === year;
    }
  }

  function isToday(day) {
    const today = new Date();
    const [year, month, date] = formatDate(today).split('-');
    return parseInt(date) === day && parseInt(month) === selectedMonth + 1 && parseInt(year) === selectedYear;
  }

  function isAvailable(day) {
    if (!monthAvailability[selectedYear + '-' + (selectedMonth + 1)]) {
      return false;
    }
    // if is before today
    const today = new Date();
    if (selectedYear < today.getFullYear() || (selectedYear === today.getFullYear() && selectedMonth < today.getMonth()) || (selectedYear === today.getFullYear() && selectedMonth === today.getMonth() && day < today.getDate())) {
      return false;
    }
    const d = day < 10 ? '0' + day : day;
    return monthAvailability[selectedYear + '-' + (selectedMonth + 1)][d];
  }

  function nextMonth (e) {
    e.preventDefault();
    if (selectedMonth === 11) {
      setSelectedYear(selectedYear + 1);
      setSelectedMonth(0);
      return;
    }
    setSelectedMonth(selectedMonth + 1);
  }

  function previousMonth (e) {
    e.preventDefault();
    if (selectedMonth === 0) {
      setSelectedYear(selectedYear - 1);
      setSelectedMonth(11);
      return;
    }
    setSelectedMonth(selectedMonth - 1);
  }

  function getDaysInMonth(month, year) {
    return new Date(year, month + 1, 0).getDate();
  }

  function getFirstDayOfMonth(month, year) {    
    return new Date(year, month, 1).getDay();
  }

  return (
    <div className="vz-availability-calendar">
      <header>
        <button className="vz-calendar-nav__button --prev" onClick={(e) => previousMonth(e) }>
          {_vz('previous')}
        </button>
        <h1 className="vz-am__title">{Month(selectedMonth)} {selectedYear}</h1>
        <button className="vz-calendar-nav__button --next" onClick={(e) => nextMonth(e) }>
          {_vz('next')}
        </button>
      </header>
      <div className={
        `vz-calendar-grid ` +
        (monthIsLoading[selectedYear + '-' + (selectedMonth + 1)] ? '--loading' : '')
      }>
        {_vz('weekdays').split(',').map((day, index) => (
          <div className="day --header" key={index}>{day}</div>
        ))}
        {
          Array(getFirstDayOfMonth(selectedMonth, selectedYear)).fill(null).map((_, index) => (
            <div className="day --fill" key={index}></div>
          ))
        }
        {
          Array(getDaysInMonth(selectedMonth, selectedYear)).fill(null).map((_, index) => (
            <div className={`day --monthday ` + 
              ((index + 1 === selectedDay) ? ' --selected' : '') +
              (isToday(index + 1) ? ' --istoday' : '') +
              (dayIsHighlighted(index + 1) ? ' --highlighted' : '') +
              (isAvailable(index + 1) ? ' --available' : ' --unavailable')
            } key={index}>
              <button
                onClick={() => setSelectedDay( index + 1)}
                className="day-button"
              >
                {index + 1}
              </button>
            </div>
          ))
        }
      </div>
    </div>
  )
}