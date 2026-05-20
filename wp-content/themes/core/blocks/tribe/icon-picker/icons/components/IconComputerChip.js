import * as React from 'react';
const SvgIconComputerChip = ( props ) => (
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
			d="M22 8V2M42 8V2M32 8V2M8 42H2M8 22H2M8 32H2M62 42h-6M62 22h-6M62 32h-6M22 62v-6M42 62v-6M32 62v-6M52 8H12a4 4 0 0 0-4 4v40a4 4 0 0 0 4 4h40a4 4 0 0 0 4-4V12a4 4 0 0 0-4-4Z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M46 18H18v28h28z"
		/>
	</svg>
);
export default SvgIconComputerChip;
