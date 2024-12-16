import React, { useState } from 'react';
import './time-input.scss';

function TotalTimeInput ({minutes, setMinutes, name = 'total_time'}) {

  const [h, setH] = useState(Math.floor(minutes / 60));
  const [min, setMin] = useState(minutes % 60);

  const updateTotalTime = () => {
    return (e) => {
      const {name, value} = e.target;
      const v = parseInt(value);
      if (name === 'hours') {
        setH(v);
        setMinutes(v * 60 + min);
      } else {
        setMin(v);
        setMinutes(h * 60 + v);
      }
    }
  }

  return (
    <div className="vz-am-time-input">
      <input type="number"
             className="hours"
             name="hours"
             value={h}
             onChange={updateTotalTime()} />
      <span> : </span>
      <input type="number"
              className="minutes"
             name="minutes"
             value={min}
             onChange={updateTotalTime()} />
      <input type="hidden"
              name={name}
              value={minutes}
      
      />
    </div>
  );
}

export default TotalTimeInput;