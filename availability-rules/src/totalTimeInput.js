import React, { useEffect, useState } from 'react';
import './time-input.scss';

function TotalTimeInput ({minutes, setMinutes, name = 'total_time'}) {

  const [h, setH] = useState(Math.floor(minutes / 60));
  const [min, setMin] = useState(minutes % 60);

  const updateTotalTime = (name = 'min') => {
    return (e) => {
      const value = e.target.value;
      const v = parseInt(value);
      if (v < 0 && minutes <= 0) {
        setH(0);
        setMin(0);
        setMinutes(0);
        return;
      }
      if (name === 'h') {
        setH(v);
        setMinutes(v * 60 + min);
      } else if (v > 59) {
        setH(h + 1);
        setMin(v - 60);
        setMinutes((h + 1) * 60 + (v - 60));
        return;
      } else if (v < 0) {
        setH(h - 1);
        setMin(60 + v);
        setMinutes((h - 1) * 60 + (60 + v));
        return;
      } else {
        setMin(v);
        setMinutes(h * 60 + v);
      }
    }
  }

  useEffect(() => {
    setH(Math.floor(minutes / 60));
    setMin(minutes % 60);
  }, [minutes]);

  return (
    <div className="vz-am-time-input">
      <input type="number"
             className="hours"
             value={h}
             min="0"
             onChange={updateTotalTime('h')} />
      <span> : </span>
      <input type="number"
              className="minutes"
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