import * as React from 'react';
const SvgIconHandMoney = ( props ) => (
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
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M10 32h2c4.536 0 9.652 2.494 12 6h8c4.014 0 8 3.986 8 8H22"
		/>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M38.722 42H52c7.25 0 8 4 8 4L31.36 57.632a8.6 8.6 0 0 1-7.034-.292L10 50.064"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M48 32a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM10 28H2v26h8zM24 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"
		/>
	</svg>
);
export default SvgIconHandMoney;
