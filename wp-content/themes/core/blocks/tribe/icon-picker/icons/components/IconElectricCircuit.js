import * as React from 'react';
const SvgIconElectricCircuit = ( props ) => (
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
			d="M18 16v8l8 8v28M8 40v8l6 6v6M56 40v8l-6 6v6M46 16v8l-8 8v28M18 16a6 6 0 1 0 0-12 6 6 0 0 0 0 12ZM8 40a6 6 0 1 0 0-12 6 6 0 0 0 0 12ZM56 40a6 6 0 1 0 0-12 6 6 0 0 0 0 12ZM46 16a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z"
		/>
	</svg>
);
export default SvgIconElectricCircuit;
