import * as React from 'react';
const SvgIconSettingsGear = ( props ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="1em"
		height="1em"
		fill="none"
		viewBox="0 0 64 64"
		{ ...props }
	>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M32 40a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M54 32c0-1.46-.146-2.918-.434-4.35l7.414-5.45-6-10.4-8.454 3.708a21.9 21.9 0 0 0-7.508-4.344L38 2H26l-1.018 9.164a21.9 21.9 0 0 0-7.508 4.344L9.02 11.8l-6 10.4 7.414 5.454a22 22 0 0 0 0 8.7L3.02 41.8l6 10.4 8.454-3.704a21.9 21.9 0 0 0 7.508 4.344L26 62h12l1.018-9.164a21.9 21.9 0 0 0 7.508-4.344L54.98 52.2l6-10.4-7.414-5.454c.288-1.43.433-2.887.434-4.346Z"
		/>
	</svg>
);
export default SvgIconSettingsGear;
