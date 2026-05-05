import * as React from 'react';
const SvgIconWifiSignal = ( props ) => (
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
			d="M32 54a6 6 0 1 0 0-12 6 6 0 0 0 0 12ZM47.6 32.4c-4-4-9.4-6.4-15.6-6.4-6 0-11.6 2.4-15.6 6.4M59.2 21.2C52.2 14.4 42.6 10 32 10S11.8 14.4 4.8 21.2"
		/>
	</svg>
);
export default SvgIconWifiSignal;
